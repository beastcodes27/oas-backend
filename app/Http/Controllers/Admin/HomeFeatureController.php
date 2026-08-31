<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderHomeFeaturesRequest;
use App\Http\Requests\StoreHomeFeatureRequest;
use App\Http\Requests\UpdateHomeFeatureRequest;
use App\Http\Resources\HomeFeatureResource;
use App\Models\HomeFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HomeFeatureController extends Controller
{
    /**
     * List all home features for management.
     */
    public function index(): AnonymousResourceCollection
    {
        return HomeFeatureResource::collection(
            HomeFeature::query()->orderBy('sort_order')->orderBy('id')->get(),
        );
    }

    /**
     * Create a new home feature.
     */
    public function store(StoreHomeFeatureRequest $request): HomeFeatureResource
    {
        $feature = HomeFeature::query()->create([
            ...$request->validated(),
            'sort_order' => HomeFeature::query()->count(),
        ]);

        return new HomeFeatureResource($feature);
    }

    /**
     * Update a home feature.
     */
    public function update(UpdateHomeFeatureRequest $request, HomeFeature $homeFeature): HomeFeatureResource
    {
        $homeFeature->update($request->validated());

        return new HomeFeatureResource($homeFeature->fresh());
    }

    /**
     * Delete a home feature.
     */
    public function destroy(HomeFeature $homeFeature): JsonResponse
    {
        $homeFeature->delete();

        return response()->json(['message' => 'Feature removed.']);
    }

    /**
     * Reorder home features by applying the given id list as the new sort order.
     */
    public function reorder(ReorderHomeFeaturesRequest $request): JsonResponse
    {
        foreach ($request->validated('ids') as $index => $id) {
            HomeFeature::query()->whereKey($id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Order updated.']);
    }
}
