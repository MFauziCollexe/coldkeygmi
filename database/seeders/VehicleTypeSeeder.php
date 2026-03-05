<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $vehicleTypes = [
            'Motor',
            'BOX Reefer',
            'Grandmax',
            'CCD LONG REEFER',
            'Tamu / Visitor',
            'CDD BOX',
            'CDE BOX',
            'Pickup Box',
            'Pickup Terbuka',
            'Van',
            'CDE Bak Terbuka',
            'CDE Reefer',
            'CDD Bak Terbuka',
            'CDD Reefer',
            'Wingbox',
            'Tronton',
            'Fuso',
            'Fuso Reefer',
            'Build Up',
            'Build Up Reefer',
            'Container 20Ft Reefer',
            'Container 20Ft',
            'Container 40Ft Reefer',
            'Container 40Ft',
        ];

        foreach ($vehicleTypes as $name) {
            VehicleType::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}

