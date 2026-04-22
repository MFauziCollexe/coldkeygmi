<?php

namespace App\Http\Controllers;

use App\Models\CompressPdfJob;
use App\Services\PdfCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompressPdfController extends Controller
{
    public function __construct(
        private PdfCompressionService $compressionService,
    ) {
    }

    public function index(): Response
    {
        $query = CompressPdfJob::query();

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        $jobs = $query
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Tools/CompressPdf/Index', [
            'jobs' => $jobs,
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:102400'],
            'compression_level' => ['required', 'in:low,medium,high'],
        ]);

        $compressionLevel = $validated['compression_level'];
        $processedJobs = [];
        $failedFiles = [];

        foreach ($validated['files'] as $file) {
            $job = null;

            try {
                $filename = $this->sanitizeOriginalFilename($file->getClientOriginalName());
                $storedFilename = Str::uuid()->toString() . '_' . $filename;
                $storagePath = 'pdf-uploads/' . auth()->id() . '/' . $storedFilename;

                Storage::disk('local')->putFileAs(
                    'pdf-uploads/' . auth()->id(),
                    $file,
                    $storedFilename
                );

                $job = CompressPdfJob::create([
                    'user_id' => auth()->id(),
                    'original_filename' => $filename,
                    'original_path' => $storagePath,
                    'original_size' => (int) $file->getSize(),
                    'compression_level' => $compressionLevel,
                    'status' => 'pending',
                ]);

                $this->compressionService->compress($job);
                $job->refresh();
                $processedJobs[] = $job;

                if ($job->isFailed()) {
                    $failedFiles[] = [
                        'filename' => $filename,
                        'message' => $job->error_message ?: 'Compression failed.',
                    ];
                }
            } catch (\Exception $exception) {
                if ($job) {
                    $job->update([
                        'status' => 'failed',
                        'error_message' => $exception->getMessage(),
                        'completed_at' => now(),
                    ]);
                    $processedJobs[] = $job->refresh();
                }

                $failedFiles[] = [
                    'filename' => $file->getClientOriginalName(),
                    'message' => $exception->getMessage(),
                ];

                \Log::error('PDF upload failed', [
                    'filename' => $file->getClientOriginalName(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $successCount = collect($processedJobs)->where('status', 'completed')->count();
        $failedCount = count($failedFiles);
        $message = $successCount > 0
            ? $successCount . ' file(s) berhasil diproses.'
            : 'Tidak ada file yang berhasil diproses.';

        if ($failedCount > 0) {
            $message .= ' ' . $failedCount . ' file(s) gagal.';
        }

        return response()->json([
            'success' => $successCount > 0,
            'jobs' => $processedJobs,
            'failed_files' => $failedFiles,
            'message' => $message,
        ], $successCount > 0 ? 200 : 422);
    }

    public function download(Request $request, CompressPdfJob $compressPdfJob): mixed
    {
        if ($compressPdfJob->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        if (!$compressPdfJob->isCompleted()) {
            return response()->json([
                'message' => 'File is not ready for download',
            ], 400);
        }

        return $this->compressionService->downloadCompressed($compressPdfJob);
    }

    public function show(CompressPdfJob $compressPdfJob): JsonResponse
    {
        if ($compressPdfJob->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        return response()->json($compressPdfJob->load('user'));
    }

    public function delete(Request $request, CompressPdfJob $compressPdfJob): JsonResponse
    {
        if ($compressPdfJob->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        try {
            $this->compressionService->deleteJob($compressPdfJob);

            return response()->json([
                'success' => true,
                'message' => 'Job and files deleted successfully',
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    public function batch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:104857600'],
            'compression_level' => ['required', 'in:low,medium,high'],
        ]);

        // Process batch uploads
        return $this->upload($request);
    }

    public function getStats(Request $request): JsonResponse
    {
        $query = CompressPdfJob::query();

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        $stats = [
            'total_jobs' => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'processing' => (clone $query)->where('status', 'processing')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'total_original_size' => (clone $query)->sum('original_size'),
            'total_compressed_size' => (clone $query)->sum('compressed_size'),
            'avg_compression_ratio' => round(
                (float) ((clone $query)->where('status', 'completed')->avg('compression_ratio') ?? 0),
                2
            ),
        ];

        return response()->json($stats);
    }

    private function sanitizeOriginalFilename(string $filename): string
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $safeBaseName = Str::limit(Str::slug($baseName, '_'), 120, '');

        if ($safeBaseName === '') {
            $safeBaseName = 'document';
        }

        return $safeBaseName . '.' . ($extension === 'pdf' ? 'pdf' : 'pdf');
    }
}
