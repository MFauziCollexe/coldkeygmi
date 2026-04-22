<?php

namespace App\Http\Controllers;

use App\Models\CompressPdfJob;
use App\Services\PdfCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $jobs = CompressPdfJob::where('user_id', auth()->id())
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
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:104857600'], // 100MB max
            'compression_level' => ['required', 'in:low,medium,high'],
        ]);

        $compressionLevel = $validated['compression_level'];
        $uploadedJobs = [];

        foreach ($validated['files'] as $file) {
            try {
                // Store original file
                $filename = $file->getClientOriginalName();
                $storagePath = 'pdf-uploads/' . auth()->id() . '/' . uniqid() . '_' . $filename;
                Storage::disk('local')->putFileAs(
                    'pdf-uploads/' . auth()->id(),
                    $file,
                    basename($storagePath)
                );

                // Create job record
                $job = CompressPdfJob::create([
                    'user_id' => auth()->id(),
                    'original_filename' => $filename,
                    'original_path' => $storagePath,
                    'compression_level' => $compressionLevel,
                    'status' => 'pending',
                ]);

                // Start compression (can be queued for large files)
                $this->compressionService->compress($job);

                $uploadedJobs[] = $job->load('user');
            } catch (\Exception $exception) {
                \Log::error('PDF upload failed: ' . $exception->getMessage());
            }
        }

        return response()->json([
            'success' => !empty($uploadedJobs),
            'jobs' => $uploadedJobs,
            'message' => count($uploadedJobs) . ' file(s) uploaded and queued for compression.',
        ]);
    }

    public function download(Request $request, CompressPdfJob $job): mixed
    {
        // Authorization check
        if ($job->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        if (!$job->isCompleted()) {
            return response()->json([
                'message' => 'File is not ready for download',
            ], 400);
        }

        return $this->compressionService->downloadCompressed($job);
    }

    public function show(CompressPdfJob $job): JsonResponse
    {
        // Authorization check
        if ($job->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        return response()->json($job->load('user'));
    }

    public function delete(Request $request, CompressPdfJob $job): JsonResponse
    {
        // Authorization check
        if ($job->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        try {
            $this->compressionService->deleteJob($job);

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
        $userId = auth()->id();

        $stats = [
            'total_jobs' => CompressPdfJob::where('user_id', $userId)->count(),
            'completed' => CompressPdfJob::where('user_id', $userId)->where('status', 'completed')->count(),
            'processing' => CompressPdfJob::where('user_id', $userId)->where('status', 'processing')->count(),
            'failed' => CompressPdfJob::where('user_id', $userId)->where('status', 'failed')->count(),
            'total_original_size' => CompressPdfJob::where('user_id', $userId)->sum('original_size'),
            'total_compressed_size' => CompressPdfJob::where('user_id', $userId)->sum('compressed_size'),
            'avg_compression_ratio' => CompressPdfJob::where('user_id', $userId)
                ->where('status', 'completed')
                ->avg('compression_ratio') ?? 0,
        ];

        return response()->json($stats);
    }
}
