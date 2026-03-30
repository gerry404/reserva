<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business  = $request->user()->business;
        $services  = $business->allServices()->get();

        return response()->json($services);
    }

    public function store(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'duration'    => 'required|integer|min:5|max:480',
            'price'       => 'required|numeric|min:0',
            'category'    => 'nullable|string|max:100',
            'color'       => 'nullable|string|max:7',
        ]);

        $service = $business->allServices()->create(array_merge($validated, [
            'is_active' => true,
            'color'     => $validated['color'] ?? '#6366f1',
        ]));

        return response()->json($service, 201);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        $this->authorizeService($request, $service);
        return response()->json($service);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $this->authorizeService($request, $service);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:500',
            'duration'    => 'sometimes|integer|min:5|max:480',
            'price'       => 'sometimes|numeric|min:0',
            'category'    => 'sometimes|nullable|string|max:100',
            'color'       => 'sometimes|nullable|string|max:7',
            'is_active'   => 'sometimes|boolean',
        ]);

        $service->update($validated);

        return response()->json($service->fresh());
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->authorizeService($request, $service);

        if ($service->bookings()->upcoming()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un service avec des réservations à venir.'
            ], 422);
        }

        $service->delete();

        return response()->json(['message' => 'Service supprimé.']);
    }

    public function toggle(Request $request, Service $service): JsonResponse
    {
        $this->authorizeService($request, $service);
        $service->update(['is_active' => !$service->is_active]);
        return response()->json($service->fresh());
    }

    private function authorizeService(Request $request, Service $service): void
    {
        $business = $request->user()->business;
        abort_unless($service->business_id === $business->id, 403);
    }
}
