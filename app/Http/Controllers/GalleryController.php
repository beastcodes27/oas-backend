<?php

namespace App\Http\Controllers;

use App\Http\Resources\GalleryItemResource;
use App\Models\GalleryItem;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GalleryController extends Controller
{
    /**
     * List the publicly visible gallery items.
     */
    public function index(): AnonymousResourceCollection
    {
        return GalleryItemResource::collection(
            GalleryItem::query()->orderBy('sort_order')->orderBy('id')->get(),
        );
    }
}
