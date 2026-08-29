<?php

namespace App\Models;

use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GalleryItem extends Model
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * Absolute public URL for the stored image.
     */
    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image);
    }
}
