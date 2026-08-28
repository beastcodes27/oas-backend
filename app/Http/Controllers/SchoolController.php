<?php

namespace App\Http\Controllers;

use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SchoolController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SchoolResource::collection(
            School::query()->orderBy('name')->get(),
        );
    }
}
