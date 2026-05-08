<?php

namespace Tests\Feature\Plugging;

use App\Models\Customer;
use App\Models\Plugging;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PluggingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_plugging_with_custom_vehicle_type(): void
    {
        $customer = Customer::create([
            'name' => 'PT Cold Key Logistics',
            'code' => 'CKL',
            'is_active' => true,
        ]);
        $requester = $this->createUser([], 'gmium.plugging');

        $response = $this
            ->actingAs($requester)
            ->post(route('plugging.store'), [
                'tanggal' => '2026-05-08',
                'jam_mulai' => '08:00',
                'jam_selesai' => '10:30',
                'customer_id' => $customer->id,
                'vehicle_type_name' => 'Box Reefer',
                'no_container_no_polisi' => 'B 1234 XYZ',
                'suhu_awal' => 2.5,
                'suhu_akhir' => 3.1,
                'lokasi' => 'Dock 1',
                'transporter' => 'Trans Jaya',
                'jumlah_kendaraan' => 1,
                'pintu_loading' => 'A1',
                'keterangan' => 'Initial plugging request.',
            ]);

        $response->assertRedirect(route('plugging.index'));
        $response->assertSessionHas('success');

        $plugging = Plugging::query()->firstOrFail();

        $this->assertSame($customer->name, $plugging->customer);
        $this->assertSame('Box Reefer', $plugging->jenis_kendaraan);
        $this->assertSame(150, $plugging->durasi_menit);
        $this->assertSame('active', $plugging->status);
        $this->assertDatabaseHas('vehicle_types', [
            'name' => 'Box Reefer',
            'is_active' => 1,
        ]);
    }

    public function test_only_operational_manager_can_approve_plugging(): void
    {
        Storage::fake('public');

        $requester = $this->createUser([], 'gmium.plugging');
        $plugging = Plugging::create([
            'tanggal' => '2026-05-08',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'customer' => 'PT Cold Key Logistics',
            'no_container_no_polisi' => 'B 1234 XYZ',
            'suhu_awal' => 2.5,
            'suhu_akhir' => 3.0,
            'lokasi' => 'Dock 2',
            'user_id' => $requester->id,
            'status' => 'active',
            'durasi_menit' => 60,
        ]);

        $unauthorizedDepartment = $this->createDepartment([
            'name' => 'Information Technology',
            'code' => 'IT',
        ]);
        $unauthorizedUser = $this->createUser([
            'department' => $unauthorizedDepartment,
        ], 'gmium.plugging.approval');

        $forbiddenResponse = $this
            ->actingAs($unauthorizedUser)
            ->from(route('plugging.approval.index'))
            ->put(route('plugging.approve', $plugging), [
                'pic_customer' => 'Jane Customer',
                'signature_image' => UploadedFile::fake()->image('signature.png'),
            ]);

        $forbiddenResponse->assertRedirect(route('plugging.index'));
        $forbiddenResponse->assertSessionHas('error');

        $opsDepartment = $this->createDepartment([
            'name' => 'Operations',
            'code' => 'OPS',
        ]);
        $managerPosition = $this->createPosition($opsDepartment, [
            'name' => 'Operations Manager',
            'code' => 'OPS-MGR',
            'is_manager' => true,
        ]);
        $approver = $this->createUser([
            'department' => $opsDepartment,
            'position' => $managerPosition,
        ], 'gmium.plugging.approval');

        $approveResponse = $this
            ->actingAs($approver)
            ->from(route('plugging.approval.index'))
            ->put(route('plugging.approve', $plugging), [
                'pic_customer' => 'Jane Customer',
                'signature_image' => UploadedFile::fake()->image('signature.png'),
            ]);

        $approveResponse->assertRedirect(route('plugging.approval.index'));
        $approveResponse->assertSessionHas('success');

        $plugging->refresh();
        $this->assertSame('selesai', $plugging->status);
        $this->assertSame('Jane Customer', $plugging->pic_customer);
        $this->assertNotNull($plugging->signature_image);
        Storage::disk('public')->assertExists($plugging->signature_image);
    }
}
