<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChecklistMediaController extends Controller
{
    public function uploadPatroliSecurityPhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
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
}
