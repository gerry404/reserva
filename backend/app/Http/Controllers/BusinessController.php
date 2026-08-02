<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Models\User;
use App\Rules\PhoneNumber;
use App\Support\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class BusinessController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user()->business);
    }

    public function update(UpdateBusinessRequest $request): JsonResponse
    {
        $business = $request->user()->business;
        $data     = $request->safe()->except(['logo', 'cover_image']);

        // A custom link is a paid feature; a free account keeps the slug it was
        // given. Enforced here rather than in the request so the merchant gets
        // an explanation instead of a validation error they cannot act on.
        if (isset($data['slug']) && $data['slug'] !== $business->slug && ! $request->user()->hasPlan(User::PLAN_PRO)) {
            return response()->json([
                'message'       => 'Le lien personnalisé est réservé aux abonnés Pro.',
                'required_plan' => User::PLAN_PRO,
                'errors'        => ['slug' => ['Passez au plan Pro pour personnaliser votre lien.']],
            ], 402);
        }

        // Changing country realigns the clock and currency, unless the merchant
        // is overriding them in the same request.
        if (isset($data['country']) && $data['country'] !== $business->country) {
            $defaults = Business::defaultsForCountry($data['country']);
            $data['timezone'] ??= $defaults['timezone'];
            $data['currency'] ??= $defaults['currency'];
        }

        foreach (['logo' => 'logos', 'cover_image' => 'covers'] as $field => $folder) {
            if ($request->hasFile($field)) {
                $data[$field] = $this->replaceImage($request->file($field), $folder, $business->{$field});
            }
        }

        $business->update($data);

        return response()->json($business->fresh());
    }

    /**
     * First-run setup for accounts created through Google, which arrive with no
     * business attached.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->business) {
            return response()->json(['message' => 'Votre commerce est déjà configuré.'], 409);
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'min:2', 'max:120'],
            'category' => ['required', 'string', 'max:100'],
            'city'     => ['required', 'string', 'max:100'],
            'country'  => ['nullable', 'string', 'size:2'],
            'phone'    => ['required', 'string', new PhoneNumber()],
        ]);

        $country  = strtoupper($validated['country'] ?? 'CM');
        $defaults = Business::defaultsForCountry($country);

        $business = $user->business()->create([
            'name'                   => $validated['name'],
            'category'               => $validated['category'],
            'city'                   => $validated['city'],
            'country'                => $country,
            'timezone'               => $defaults['timezone'],
            'currency'               => $defaults['currency'],
            'phone'                  => $validated['phone'],
            'whatsapp'               => $validated['phone'],
            'working_hours'          => Business::defaultWorkingHours(),
            'slot_duration'          => 30,
            'booking_notice'         => 60,
            'notifications_whatsapp' => true,
            'notifications_email'    => true,
            'notifications_sms'      => false,
            'is_active'              => true,
            'accent_color'           => '#6366f1',
        ]);

        // Keep the merchant's phone on the account too, so notifications and
        // support have something to reach them on.
        $user->forceFill(['phone' => $validated['phone']])->save();

        return response()->json($business, 201);
    }

    /**
     * Store a new image and drop the one it replaces.
     *
     * Deleting after the upload succeeds, never before: a failed upload must not
     * also cost the merchant the logo they already had.
     */
    private function replaceImage(UploadedFile $file, string $folder, ?string $previous): string
    {
        $path = Uploads::store($file, $folder);

        if ($previous && $previous !== $path) {
            Uploads::delete([$previous]);
        }

        return $path;
    }
}
