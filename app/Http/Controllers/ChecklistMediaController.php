<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChecklistMediaController extends Controller
{
    public function uploadPatroliSecurityPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/patroli-security/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deletePatroliSecurityPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/patroli-security/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadCleaningOBPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/cleaning-ob/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteCleaningOBPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/cleaning-ob/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadSiteVisitMaintenancePhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/site-visit-maintenance/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteSiteVisitMaintenancePhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/site-visit-maintenance/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadChecklistITPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/checklist-it/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteChecklistITPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/checklist-it/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadRunningGensetPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/running-genset/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteRunningGensetPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/running-genset/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadKompresorHarianPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/kompresor-harian/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteKompresorHarianPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/kompresor-harian/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadChargerBateraiPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/charger-baterai/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteChargerBateraiPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/charger-baterai/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadChecklistBateraiPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/checklist-baterai/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteChecklistBateraiPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/checklist-baterai/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadInspeksiLokerPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/inspeksi-loker/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteInspeksiLokerPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/inspeksi-loker/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadWarehousePhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/warehouse/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteWarehousePhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/warehouse/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }

    public function uploadSaranaPrasaranaPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $userId = (int) optional($request->user())->id;
        $directory = 'checklist/sarana-prasarana/' . now()->format('Y/m');
        if ($userId > 0) {
            $directory .= '/user-' . $userId;
        }

        $path = Storage::disk('public')->putFile($directory, $validated['photo']);
        if (!$path) {
            return response()->json([
                'message' => 'Foto gagal di-upload.',
            ], 422);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }

    public function deleteSaranaPrasaranaPhoto(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path']);
        if ($path === '' || !str_starts_with($path, 'checklist/sarana-prasarana/')) {
            return response()->json([
                'message' => 'Path foto tidak valid.',
            ], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
        ]);
    }
}
