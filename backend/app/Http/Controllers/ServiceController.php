<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        $services = $business->allServices()->get()
            // The parent is in hand; setting it back keeps price formatting from
            // firing one query per service.
            ->each(fn (Service $service) => $service->setRelation('business', $business));

        return ServiceResource::collection($services)->response();
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $business = $request->user()->business;

        $service = $business->allServices()->create([
            ...$request->safe()->except(['images', 'existing_images']),
            'color'     => $request->input('color') ?: '#6366f1',
            'is_active' => true,
            'images'    => $this->storeUploads($request) ?: null,
        ]);

        $service->setRelation('business', $business);

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function show(Request $request, Service $service): ServiceResource
    {
        $this->authorize('view', $service);

        return new ServiceResource($service);
    }

    public function update(ServiceRequest $request, Service $service): ServiceResource
    {
        $this->authorize('update', $service);

        $data = $request->safe()->except(['images', 'existing_images']);

        if ($request->has('existing_images') || $request->hasFile('images')) {
            $data['images'] = $this->reconcileImages($request, $service);
        }

        $service->update($data);

        return new ServiceResource($service->fresh());
    }

    public function toggle(Request $request, Service $service): ServiceResource
    {
        $this->authorize('update', $service);

        $service->update(['is_active' => ! $service->is_active]);

        return new ServiceResource($service->fresh());
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->authorize('delete', $service);

        // Refusing rather than cascading: those customers are expecting to be
        // seen, and the merchant should cancel them deliberately.
        if ($service->bookings()->upcoming()->exists()) {
            return response()->json([
                'message' => 'Ce service a des réservations à venir. Désactivez-le plutôt que de le supprimer.',
            ], 422);
        }

        Uploads::delete($service->images ?? []);
        $service->delete();

        return response()->json(['message' => 'Service supprimé.']);
    }

    // ─── Internals ───────────────────────────────────────────────────────

    /** @return list<string> */
    private function storeUploads(ServiceRequest $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        return collect($request->file('images'))
            ->map(fn ($file) => Uploads::store($file, 'services'))
            ->all();
    }

    /**
     * Merge kept images with newly uploaded ones and bin the rest.
     *
     * `existing_images` is filtered against what the service actually owns:
     * without that check, a crafted request could name any path on the public
     * disk and have it deleted, or adopted into somebody else's gallery.
     *
     * @return list<string>|null
     */
    private function reconcileImages(ServiceRequest $request, Service $service): ?array
    {
        $current = $service->images ?? [];

        $kept = array_values(array_intersect(
            $current,
            $request->input('existing_images', []),
        ));

        Uploads::delete(array_diff($current, $kept));

        $images = array_slice([...$kept, ...$this->storeUploads($request)], 0, Service::MAX_IMAGES);

        return $images ?: null;
    }
}
