<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use App\Mail\WelcomeNotification;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:users,email',
            'password'          => 'required|string|min:8|confirmed',
            'phone'             => 'required|string|max:20',
            'business_name'     => 'required|string|max:255',
            'business_category' => 'required|string|max:100',
            'business_city'     => 'required|string|max:100',
        ]);

        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
            'phone'           => $validated['phone'],
            'plan'            => 'pro',
            'plan_expires_at' => now()->addDays(14),
        ]);

        $business = Business::create([
            'user_id'       => $user->id,
            'name'          => $validated['business_name'],
            'category'      => $validated['business_category'],
            'city'          => $validated['business_city'],
            'country'       => 'CM',
            'phone'         => $validated['phone'],
            'whatsapp'      => $validated['phone'],
            'working_hours' => $this->defaultWorkingHours(),
            'slot_duration' => 30,
            'booking_notice' => 60,
            'notifications_whatsapp' => true,
            'notifications_sms'      => false,
            'is_active'     => true,
            'accent_color'  => '#6366f1',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Send welcome email
        try {
            Mail::to($user->email)->queue(new WelcomeNotification($user));
        } catch (\Exception $e) {
            \Log::error('Welcome email failed: ' . $e->getMessage());
        }

        return response()->json([
            'token'    => $token,
            'user'     => $user->load('business'),
            'business' => $business,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token'    => $token,
            'user'     => $user->load('business'),
            'business' => $user->business,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user'     => $request->user()->load('business'),
            'business' => $request->user()->business,
        ]);
    }

    /**
     * Handle Google OAuth callback from frontend.
     * Frontend sends the Google access token obtained from Google Sign-In.
     */
    public function googleCallback(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token Google invalide.'], 401);
        }

        // Find existing user by google_id or email
        $user = User::where('google_id', $googleUser->getId())
                     ->orWhere('email', $googleUser->getEmail())
                     ->first();

        if ($user) {
            // Update google_id and avatar if not set
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
            ]);
        } else {
            // Create new user with 14-day Pro trial
            $user = User::create([
                'name'            => $googleUser->getName(),
                'email'           => $googleUser->getEmail(),
                'google_id'       => $googleUser->getId(),
                'avatar'          => $googleUser->getAvatar(),
                'plan'            => 'pro',
                'plan_expires_at' => now()->addDays(14),
            ]);

            try {
                Mail::to($user->email)->queue(new WelcomeNotification($user));
            } catch (\Exception $e) {
                \Log::error('Welcome email failed: ' . $e->getMessage());
            }
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        $hasBusiness = $user->business !== null;

        return response()->json([
            'token'        => $token,
            'user'         => $user->load('business'),
            'business'     => $user->business,
            'needs_setup'  => !$hasBusiness,
        ]);
    }

    private function defaultWorkingHours(): array
    {
        return [
            'lundi'    => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
            'mardi'    => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
            'mercredi' => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
            'jeudi'    => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
            'vendredi' => ['is_open' => true,  'open' => '08:00', 'close' => '18:00'],
            'samedi'   => ['is_open' => true,  'open' => '08:00', 'close' => '14:00'],
            'dimanche' => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
        ];
    }
}
