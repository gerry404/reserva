<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BusinessController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        if (!$business) {
            return response()->json(['message' => 'Aucun commerce trouvé.'], 404);
        }

        return response()->json($business);
    }

    public function update(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        $validated = $request->validate([
            'name'                   => 'sometimes|string|max:255',
            'description'            => 'sometimes|nullable|string|max:1000',
            'category'               => 'sometimes|string|max:100',
            'address'                => 'sometimes|nullable|string|max:500',
            'city'                   => 'sometimes|string|max:100',
            'country'                => 'sometimes|string|max:100',
            'phone'                  => 'sometimes|string|max:20',
            'whatsapp'               => 'sometimes|nullable|string|max:20',
            'working_hours'          => 'sometimes|array',
            'slot_duration'          => 'sometimes|integer|in:15,30,45,60,90,120',
            'booking_notice'         => 'sometimes|integer|min:0',
            'notifications_whatsapp' => 'sometimes|boolean',
            'notifications_sms'      => 'sometimes|boolean',
            'is_active'              => 'sometimes|boolean',
            'accent_color'           => 'sometimes|string|max:7',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $request->validate(['logo' => 'image|max:2048']);
            if ($business->logo) {
                Storage::disk('public')->delete($business->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        // Handle cover upload
        if ($request->hasFile('cover_image')) {
            $request->validate(['cover_image' => 'image|max:5120']);
            if ($business->cover_image) {
                Storage::disk('public')->delete($business->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        $business->update($validated);

        return response()->json($business->fresh());
    }

    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->business) {
            return response()->json(['message' => 'Commerce déjà configuré.'], 409);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'city'     => 'required|string|max:100',
            'phone'    => 'required|string|max:20',
        ]);

        $business = $user->business()->create(array_merge($validated, [
            'country'       => 'CM',
            'working_hours' => (new \App\Models\Business())->getDefaultWorkingHours(),
            'slot_duration' => 30,
            'is_active'     => true,
            'accent_color'  => '#6366f1',
        ]));

        return response()->json($business, 201);
    }
}
