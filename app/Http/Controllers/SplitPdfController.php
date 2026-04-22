<?php

namespace App\Http\Controllers;

use App\Models\SplitPdfJob;
use App\Services\PdfToolkitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SplitPdfController extends Controller
{
    public function __construct(
        private PdfToolkitService $toolkitService,
    ) {
    }

    public function index(): Response
    {
        $query = SplitPdfJob::query();

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return Inertia::render('Tools/SplitPdf/Index', [
            'jobs' => $query->latest()->paginate(15),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:102400'],
            'split_mode' => ['required', 'in:all_pages,custom_ranges'],
            'ranges' => ['nullable', 'array'],
            'ranges.*.start' => ['nullable', 'integer', 'min:1'],
            'ranges.*.end' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validated['split_mode'] === 'custom_ranges' && empty($validated['ranges'])) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal satu range halaman diperlukan.',
            ], 422);
        }

        $stored = $this->toolkitService->storeUploadedFile(
            $validated['file'],
            $this->toolkitService->splitUploadDirectory(),
            'split_pdf'
        );

        $job = SplitPdfJob::create([
            'user_id' => auth()->id(),
            'original_filename' => $stored['original_filename'],
            'original_path' => $stored['path'],
            'page_ranges' => $validated['split_mode'] === 'custom_ranges' ? array_values($validated['ranges'] ?? []) : null,
            'split_mode' => $validated['split_mode'],
            'status' => 'pending',
        ]);

        $this->toolkitService->split($job);
        $job->refresh();

        return response()->json([
            'success' => $job->status === 'completed',
            'job' => $job,
            'message' => $job->status === 'completed'
                ? 'Split PDF berhasil diproses.'
                : ($job->error_message ?: 'Split PDF gagal diproses.'),
        ], $job->status === 'completed' ? 200 : 422);
    }

    public function download(SplitPdfJob $splitPdfJob): mixed
    {
        $this->authorizeJob($splitPdfJob->user_id);

        return $this->toolkitService->downloadSplit($splitPdfJob);
    }

    public function show(SplitPdfJob $splitPdfJob): JsonResponse
    {
        $this->authorizeJob($splitPdfJob->user_id);

        return response()->json($splitPdfJob);
    }

    public function delete(SplitPdfJob $splitPdfJob): JsonResponse
    {
        $this->authorizeJob($splitPdfJob->user_id);

        $this->toolkitService->deleteSplitJob($splitPdfJob);

        return response()->json([
            'success' => true,
            'message' => 'Job split PDF berhasil dihapus.',
        ]);
    }

    private function authorizeJob(int $userId): void
    {
        if ($userId !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
