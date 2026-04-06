<?php

namespace App\Http\Controllers;

use App\Support\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class DatabaseBackupController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backupService,
    ) {
    }

    public function index(Request $request)
    {
        return Inertia::render('ControlPanel/DatabaseBackup', [
            'connection' => (string) config('database.default', ''),
            'databaseName' => $this->backupService->databaseName(),
            'backups' => $this->backupService->listBackups(),
            'backupPath' => $this->backupService->backupStoragePath(),
            'backupPathInput' => $this->backupService->backupStoragePathInput(),
            'scheduler' => $this->backupService->schedulerStatus(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $backupFile = $this->backupService->createBackup();
        } catch (\Throwable $e) {
            return redirect()
                ->route('control.database-backup')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('control.database-backup')
            ->with('success', 'Backup database berhasil dibuat: ' . basename($backupFile));
    }

    public function download(string $fileName)
    {
        $path = $this->backupService->resolveBackupPath($fileName);
        if (!is_file($path)) {
            abort(404);
        }

        return Response::download($path, basename($path));
    }

    public function destroy(string $fileName): RedirectResponse
    {
        $path = $this->backupService->resolveBackupPath($fileName);
        if (!is_file($path)) {
            return redirect()
                ->route('control.database-backup')
                ->with('error', 'File backup tidak ditemukan.');
        }

        File::delete($path);

        return redirect()
            ->route('control.database-backup')
            ->with('success', 'Backup database berhasil dihapus: ' . basename($path));
    }

    public function start(): RedirectResponse
    {
        try {
            $this->backupService->enableScheduler();
        } catch (\Throwable $e) {
            return redirect()
                ->route('control.database-backup')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('control.database-backup')
            ->with('success', 'Scheduler backup database berhasil diaktifkan.');
    }

    public function stop(): RedirectResponse
    {
        try {
            $this->backupService->disableScheduler();
        } catch (\Throwable $e) {
            return redirect()
                ->route('control.database-backup')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('control.database-backup')
            ->with('success', 'Scheduler backup database berhasil dimatikan.');
    }

    public function updatePath(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'backup_path' => 'nullable|string|max:500',
        ]);

        try {
            $resolvedPath = $this->backupService->updateBackupStoragePath($data['backup_path'] ?? '');
        } catch (\Throwable $e) {
            return redirect()
                ->route('control.database-backup')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('control.database-backup')
            ->with('success', 'Lokasi backup berhasil diperbarui ke: ' . $resolvedPath);
    }
}
