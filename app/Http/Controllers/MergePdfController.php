<?php

namespace App\Http\Controllers;

use App\Models\MergePdfJob;
use App\Services\PdfToolkitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MergePdfController extends Controller
{
    public function __construct(
        private PdfToolkitService $toolkitService,
    ) {
    }

    public function index(): Response
    {
        $query = MergePdfJob::query();

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return Inertia::render('Tools/MergePdf/Index', [
            'jobs' => $query->latest()->paginate(15),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:2'],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:102400'],
        ]);

        $storedFiles = collect($validated['files'])
            ->map(fn ($file) => $this->toolkitService->storeUploadedFile($file, $this->toolkitService->mergeUploadDirectory(), 'merge_pdf'))
            ->values();

        $job = MergePdfJob::create([
            'user_id' => auth()->id(),
            'input_filenames' => $storedFiles->pluck('original_filename')->all(),
            'input_paths' => $storedFiles->pluck('path')->all(),
            'status' => 'pending',
        ]);

        $this->toolkitService->merge($job);
        $job->refresh();

        return response()->json([
            'success' => $job->status === 'completed',
            'job' => $job,
            'message' => $job->status === 'completed'
                ? 'Merge PDF berhasil diproses.'
                : ($job->error_message ?: 'Merge PDF gagal diproses.'),
        ], $job->status === 'completed' ? 200 : 422);
    }

    public function download(MergePdfJob $mergePdfJob): mixed
    {
        $this->authorizeJob($mergePdfJob->user_id);

        return $this->toolkitService->downloadMerge($mergePdfJob);
    }

    public function show(MergePdfJob $mergePdfJob): JsonResponse
    {
        $this->authorizeJob($mergePdfJob->user_id);

        return response()->json($mergePdfJob);
    }

    public function delete(MergePdfJob $mergePdfJob): JsonResponse
    {
        $this->authorizeJob($mergePdfJob->user_id);

        $this->toolkitService->deleteMergeJob($mergePdfJob);

        return response()->json([
            'success' => true,
            'message' => 'Job merge PDF berhasil dihapus.',
        ]);
    }

    private function authorizeJob(int $userId): void
    {
        if ($userId !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
