<?php

namespace App\Models;

use Database\Factories\HomeFeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeFeature extends Model
{
    /** @use HasFactory<HomeFeatureFactory> */
    use HasFactory;

    protected $guarded = [];
}
