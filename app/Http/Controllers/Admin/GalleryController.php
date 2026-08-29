<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGalleryItemRequest;
use App\Http\Resources\GalleryItemResource;
use App\Models\GalleryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * List all gallery items for management.
     */
    public function index(): AnonymousResourceCollection
    {
        return GalleryItemResource::collection(
            GalleryItem::query()->orderBy('sort_order')->orderBy('id')->get(),
        );
    }

    /**
     * Upload a new gallery item.
     */
    public function store(StoreGalleryItemRequest $request): GalleryItemResource
    {
        $path = $request->file('image')->store('gallery', 'public');

        $item = GalleryItem::query()->create([
            'image' => $path,
            'caption' => $request->validated('caption'),
            'description' => $request->validated('description'),
            'sort_order' => GalleryItem::query()->count(),
        ]);

        return new GalleryItemResource($item);
    }

    /**
     * Delete a gallery item and its stored image.
     */
    public function destroy(GalleryItem $galleryItem): JsonResponse
    {
        Storage::disk('public')->delete($galleryItem->image);
        $galleryItem->delete();

        return response()->json(['message' => 'Gallery item deleted.']);
    }
}
