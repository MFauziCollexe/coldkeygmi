<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\BeritaAcara;
use App\Models\Customer;
use App\Models\Department;
use App\Support\BeritaAcaraLetterhead;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;

class BeritaAcaraController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'berita-acara');

        $search = trim((string) $request->input('search', ''));

        $query = BeritaAcara::query()
            ->with([
                'customer:id,name',
                'department:id,name,code',
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('number', 'like', '%' . $search . '%')
                        ->orWhere('document_number', 'like', '%' . $search . '%')
                        ->orWhere('event_name', 'like', '%' . $search . '%')
                        ->orWhere('event_location', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('letter_date')
            ->orderByDesc('id');

        $items = $query->paginate(10)->withQueryString();

        return Inertia::render('GMISL/Utility/BeritaAcara/Index', [
            'items' => $items,
            'filters' => $request->only(['search', 'page']),
        ]);
    }

    public function create(Request $request)
    {
        $today = now()->toDateString();
        $createdAt = Carbon::parse($today);

        return Inertia::render('GMISL/Utility/BeritaAcara/Create', [
            'defaults' => [
                'document_number' => BeritaAcara::generateDocumentNumber($createdAt),
                'ba_number' => BeritaAcara::generateNumber($createdAt),
                'created_date' => $today,
                'incident_date' => $today,
            ],
            'customers' => Customer::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'departments' => Department::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'incident_date' => ['required', 'date'],
            'incident_place' => ['required', 'string', 'max:255'],
            'incident_time' => ['required', 'date_format:H:i'],
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'department_id' => ['required', 'exists:departments,id'],
            'chronology' => ['required', 'string', 'max:20000'],
        ]);

        $nowDate = now()->toDateString();
        $createdAt = Carbon::parse($nowDate);
        $incidentDate = Carbon::parse($validated['incident_date'])->toDateString();

        $beritaAcara = BeritaAcara::create([
            'title' => 'Berita Acara',
            'number' => BeritaAcara::generateNumber($createdAt),
            'document_number' => BeritaAcara::generateDocumentNumber($createdAt),
            'letter_date' => $nowDate,
            'event_date' => $incidentDate,
            'event_name' => 'Kejadian',
            'event_location' => $validated['incident_place'],
            'start_time' => $validated['incident_time'] . ':00',
            'end_time' => $validated['incident_time'] . ':00',
            'duration_hours' => 0,
            'attendees' => [],
            'results' => [],
            'customer_id' => (int) $validated['customer_id'],
            'department_id' => (int) $validated['department_id'],
            'vehicle_no' => trim((string) ($validated['vehicle_no'] ?? '')) ?: null,
            'incident_time' => $validated['incident_time'] . ':00',
            'chronology' => $validated['chronology'],
            'created_by' => optional($request->user())->id,
        ]);

        $this->generateAndStorePdf($beritaAcara);

        return redirect()
            ->route('berita-acara.show', $beritaAcara->id)
            ->with('success', 'Berita Acara berhasil dibuat.');
    }

    public function show(Request $request, BeritaAcara $beritaAcara)
    {
        $beritaAcara->loadMissing([
            'customer:id,name',
            'department:id,name,code',
        ]);

        return Inertia::render('GMISL/Utility/BeritaAcara/Show', [
            'item' => $beritaAcara,
            'canDelete' => $this->canDelete($request),
        ]);
    }

    public function print(BeritaAcara $beritaAcara)
    {
        $beritaAcara->loadMissing([
            'customer:id,name',
            'department:id,name,code',
        ]);

        return Inertia::render('GMISL/Utility/BeritaAcara/Print', [
            'item' => $beritaAcara,
        ]);
    }

    public function downloadPdf(BeritaAcara $beritaAcara)
    {
        $beritaAcara->loadMissing([
            'customer:id,name',
            'department:id,name,code',
        ]);

        $downloadName = ($beritaAcara->number ?: 'berita-acara') . '.pdf';

        try {
            $supportsNewFingerprint = Schema::hasColumn('berita_acaras', 'ba_pdf_template')
                && Schema::hasColumn('berita_acaras', 'pdf_generated_at');
            $supportsOldFingerprint = Schema::hasColumn('berita_acaras', 'pdf_template_fingerprint')
                && Schema::hasColumn('berita_acaras', 'pdf_generated_at');

            $fingerprint = ($supportsNewFingerprint || $supportsOldFingerprint) ? $this->currentPdfTemplateFingerprint() : null;
            $storedFingerprint = $supportsNewFingerprint
                ? (string) ($beritaAcara->ba_pdf_template ?? '')
                : (string) ($beritaAcara->pdf_template_fingerprint ?? '');

            $needsRegenerate = !$beritaAcara->pdf_path
                || !Storage::disk('public')->exists($beritaAcara->pdf_path)
                || (($supportsNewFingerprint || $supportsOldFingerprint) && $storedFingerprint !== (string) $fingerprint);

            $storageWriteFailed = false;
            if ($needsRegenerate) {
                try {
                    $this->generateAndStorePdf($beritaAcara, $fingerprint);
                    $beritaAcara->refresh();
                } catch (\Throwable $e) {
                    $storageWriteFailed = true;

                    Log::warning('Berita acara PDF could not be stored. Falling back to direct download response.', [
                        'berita_acara_id' => $beritaAcara->id,
                        'message' => $e->getMessage(),
                        'pdf_path' => $beritaAcara->pdf_path,
                    ]);
                }
            }

            if (!$storageWriteFailed && $beritaAcara->pdf_path && Storage::disk('public')->exists($beritaAcara->pdf_path)) {
                $response = Storage::disk('public')->download($beritaAcara->pdf_path, $downloadName);
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
                return $response;
            }

            Log::warning('Berita acara PDF not found in storage, streaming generated bytes directly.', [
                'berita_acara_id' => $beritaAcara->id,
                'pdf_path' => $beritaAcara->pdf_path,
            ]);

            return $this->pdfBytesResponse($this->renderPdfBytes($beritaAcara), $downloadName);
        } catch (\Throwable $e) {
            Log::error('Berita acara PDF download failed.', [
                'berita_acara_id' => $beritaAcara->id,
                'message' => $e->getMessage(),
                'pdf_path' => $beritaAcara->pdf_path,
                'zip_available' => class_exists(\ZipArchive::class),
            ]);

            abort(500, 'Download PDF gagal diproses.');
        }
    }

    private function generateAndStorePdf(BeritaAcara $beritaAcara, ?string $fingerprint = null): void
    {
        $beritaAcara->loadMissing([
            'customer:id,name',
            'department:id,name,code',
        ]);

        $supportsNewFingerprint = Schema::hasColumn('berita_acaras', 'ba_pdf_template')
            && Schema::hasColumn('berita_acaras', 'pdf_generated_at');
        $supportsOldFingerprint = Schema::hasColumn('berita_acaras', 'pdf_template_fingerprint')
            && Schema::hasColumn('berita_acaras', 'pdf_generated_at');
        $supportsFingerprint = $supportsNewFingerprint || $supportsOldFingerprint;

        $fingerprint = $supportsFingerprint
            ? ($fingerprint ?: $this->currentPdfTemplateFingerprint())
            : null;
        $bytes = $this->renderPdfBytes($beritaAcara);
        $fileName = ($beritaAcara->number ?: ('ba-' . $beritaAcara->id)) . '.pdf';
        $safeFileName = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $fileName);
        $path = 'berita-acara/' . $beritaAcara->id . '/' . $safeFileName;

        if ($beritaAcara->pdf_path && Storage::disk('public')->exists($beritaAcara->pdf_path) && $beritaAcara->pdf_path !== $path) {
            Storage::disk('public')->delete($beritaAcara->pdf_path);
        }

        if (!Storage::disk('public')->put($path, $bytes)) {
            throw new \RuntimeException('Failed to write berita acara PDF to storage.');
        }

        if ($beritaAcara->pdf_path !== $path) {
            $beritaAcara->forceFill(['pdf_path' => $path])->save();
        }

        if ($supportsFingerprint) {
            $payload = [
                'pdf_generated_at' => now(),
            ];
            if ($supportsNewFingerprint) {
                $payload['ba_pdf_template'] = $fingerprint;
            }
            if ($supportsOldFingerprint) {
                $payload['pdf_template_fingerprint'] = $fingerprint;
            }

            $beritaAcara->forceFill($payload)->save();
        }
    }

    private function renderPdfBytes(BeritaAcara $beritaAcara): string
    {
        $this->assertDompdfAvailable();

        $letterheadDataUri = BeritaAcaraLetterhead::getDataUri();

        $html = view('pdf.berita_acara', [
            'item' => $beritaAcara,
            'letterheadDataUri' => $letterheadDataUri,
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', $this->ensureDompdfWorkDir('temp'));
        $options->set('fontDir', $this->ensureDompdfWorkDir('fonts'));
        $options->set('fontCache', $this->ensureDompdfWorkDir('fonts'));

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }

    private function assertDompdfAvailable(): void
    {
        if (!class_exists(Dompdf::class) || !class_exists(Options::class)) {
            throw new \RuntimeException(
                'dompdf/dompdf is not available. Run composer install on the server and refresh Composer autoload.'
            );
        }
    }

    private function ensureDompdfWorkDir(string $segment): string
    {
        $path = storage_path('app/dompdf/' . $segment);

        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException('Failed to create Dompdf directory: ' . $path);
        }

        return $path;
    }

    private function pdfBytesResponse(string $bytes, string $downloadName)
    {
        $safeDownloadName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $downloadName) ?: 'berita-acara.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $safeDownloadName . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function currentPdfTemplateFingerprint(): string
    {
        $templatePath = resource_path('views/pdf/berita_acara.blade.php');
        $templateHash = is_file($templatePath) ? sha1_file($templatePath) : '';

        $docxPath = (string) config('berita_acara.letterhead_docx_path');
        $docxSignature = '';
        if ($docxPath !== '' && is_file($docxPath)) {
            $mtime = @filemtime($docxPath);
            $size = @filesize($docxPath);
            if ($mtime !== false && $size !== false) {
                $docxSignature = (string) $mtime . '|' . (string) $size;
            }
        }

        // Ensure kop surat image exists (or refreshed) so we can hash stable bytes.
        BeritaAcaraLetterhead::getDataUri();

        $kopHash = '';
        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            $path = 'letterhead/ba_kop.' . $ext;
            if (Storage::disk('public')->exists($path)) {
                $kopHash = sha1(Storage::disk('public')->get($path));
                break;
            }
        }

        return sha1('ba_pdf_v2|' . $templateHash . '|' . $docxSignature . '|' . $kopHash);
    }

    public function destroy(Request $request, BeritaAcara $beritaAcara)
    {
        if (!$this->canDelete($request)) {
            abort(403, 'Tidak punya akses hapus Berita Acara.');
        }

        if ($beritaAcara->pdf_path) {
            Storage::disk('public')->delete($beritaAcara->pdf_path);
        }
        Storage::disk('public')->deleteDirectory('berita-acara/' . $beritaAcara->id);

        $beritaAcara->delete();

        return $this->redirectToRememberedIndex($request, 'berita-acara', 'berita-acara.index')
            ->with('success', 'Berita Acara berhasil dihapus.');
    }

    private function canDelete(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        $user->loadMissing('department:id,code');
        return strtoupper((string) optional($user->department)->code) === 'IT';
    }

    // Note: sementara input disederhanakan; field lain diisi default.
}
