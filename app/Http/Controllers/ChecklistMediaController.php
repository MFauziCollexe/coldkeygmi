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
            'old_path' => ['nullable', 'string', 'max:500'],
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

        $oldPath = trim((string) ($validated['old_path'] ?? ''));
        if ($oldPath !== '' && str_starts_with($oldPath, 'checklist/patroli-security/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Foto berhasil di-upload.',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_name' => (string) $validated['photo']->getClientOriginalName(),
        ]);
    }
}
