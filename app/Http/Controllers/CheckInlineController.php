<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\CheckInline;
use App\Models\CheckInlineImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CheckInlineController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'check-inline');

        $query = CheckInline::query()->with(['creator:id,name,email', 'updater:id,name,email', 'images:id,check_inline_id,image']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%")
                    ->orWhere('po', 'like', "%{$search}%")
                    ->orWhere('batch', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderByDesc('id')->paginate(10)->withQueryString();

        $rows->getCollection()->transform(function ($item) {
            $imageUrls = $item->images->map(function ($img) {
                return Storage::url($img->image);
            })->values()->all();

            // Backward compatibility for old single-image rows
            if (empty($imageUrls) && $item->image) {
                $imageUrls[] = Storage::url($item->image);
            }

            $item->image_urls = $imageUrls;
            return $item;
        });

        return Inertia::render('GMISL/Utility/CheckInline/Index', [
            'rows' => $rows,
            'filters' => $request->only(['search', 'page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('GMISL/Utility/CheckInline/Create');
    }

    public function show(CheckInline $checkInline)
    {
        $checkInline->load(['creator:id,name,email', 'updater:id,name,email', 'images:id,check_inline_id,image']);

        // Backfill legacy single-image column into relation so edit flow can manage it.
        if ($checkInline->images->isEmpty() && $checkInline->image) {
            CheckInlineImage::create([
                'check_inline_id' => $checkInline->id,
                'image' => $checkInline->image,
            ]);
            $checkInline->load(['images:id,check_inline_id,image']);
        }

        $existingImages = $checkInline->images->map(function ($img) {
            return [
                'id' => $img->id,
                'url' => Storage::url($img->image),
            ];
        })->values()->all();

        return Inertia::render('GMISL/Utility/CheckInline/Edit', [
            'row' => $checkInline,
            'existingImages' => $existingImages,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer' => 'required|string|max:255',
            'po' => 'required|string|max:255',
            'qty' => 'required|numeric|min:0',
            'exp' => 'required|date',
            'batch' => 'required|string|max:100',
            'date' => 'required|date',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        $now = now();
        $userId = Auth::id();
        $userName = Auth::user()?->name ?? 'System';

        $data['user_name'] = $userName;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['created_date'] = $now;
        $data['updated_date'] = $now;

        $data['image'] = null;
        $checkInline = CheckInline::create($data);

        if ($request->hasFile('images')) {
            $storedImages = [];
            foreach ($request->file('images') as $file) {
                $path = $file->store('check-inline', 'public');
                $storedImages[] = ['check_inline_id' => $checkInline->id, 'image' => $path];
            }

            if (!empty($storedImages)) {
                CheckInlineImage::insert($storedImages);
                // Keep first image on legacy column for compatibility.
                $checkInline->update(['image' => $storedImages[0]['image']]);
            }
        }

        return $this->redirectToRememberedIndex($request, 'check-inline', 'check-inline.index')
            ->with('success', 'Check Inline created successfully.');
    }

    public function update(Request $request, CheckInline $checkInline)
    {
        $data = $request->validate([
            'customer' => 'required|string|max:255',
            'po' => 'required|string|max:255',
            'qty' => 'required|numeric|min:0',
            'exp' => 'required|date',
            'batch' => 'required|string|max:100',
            'date' => 'required|date',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'keep_existing_image_ids' => 'nullable|array',
            'keep_existing_image_ids.*' => 'integer',
        ]);

        $userId = Auth::id();
        $now = now();

        $checkInline->update([
            'customer' => $data['customer'],
            'po' => $data['po'],
            'qty' => $data['qty'],
            'exp' => $data['exp'],
            'batch' => $data['batch'],
            'date' => $data['date'],
            'updated_by' => $userId,
            'updated_date' => $now,
        ]);

        $keepIds = collect($data['keep_existing_image_ids'] ?? [])->filter()->values();
        $imagesQuery = $checkInline->images();

        if ($keepIds->isNotEmpty()) {
            $toDelete = $imagesQuery->whereNotIn('id', $keepIds->all())->get();
        } else {
            $toDelete = $imagesQuery->get();
        }

        foreach ($toDelete as $img) {
            Storage::disk('public')->delete($img->image);
            $img->delete();
        }

        if ($request->hasFile('images')) {
            $insertRows = [];
            foreach ($request->file('images') as $file) {
                $path = $file->store('check-inline', 'public');
                $insertRows[] = ['check_inline_id' => $checkInline->id, 'image' => $path];
            }
            if (!empty($insertRows)) {
                CheckInlineImage::insert($insertRows);
            }
        }

        $firstImagePath = $checkInline->images()->value('image');
        $checkInline->update(['image' => $firstImagePath]);

        return $this->redirectToRememberedIndex($request, 'check-inline', 'check-inline.index')
            ->with('success', 'Check Inline updated successfully.');
    }
}
