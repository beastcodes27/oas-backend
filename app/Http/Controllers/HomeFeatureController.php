<?php

namespace App\Http\Controllers;

use App\Http\Resources\HomeFeatureResource;
use App\Models\HomeFeature;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HomeFeatureController extends Controller
{
    /**
     * List the public home features shown on the landing page.
     */
    public function index(): AnonymousResourceCollection
    {
        return HomeFeatureResource::collection(
            HomeFeature::query()->orderBy('sort_order')->orderBy('id')->get(),
        );
    }
}
