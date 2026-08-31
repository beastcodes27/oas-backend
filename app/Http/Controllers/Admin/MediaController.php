<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Upload an image and return its public URL.
     */
    public function store(UploadImageRequest $request): JsonResponse
    {
        $path = $request->file('image')->store('uploads', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
