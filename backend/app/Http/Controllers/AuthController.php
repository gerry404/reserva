<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Mail\WelcomeNotification;
use App\Models\Business;
use App\Models\User;
use App\Rules\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /** Tokens are named so a future "signed-in devices" screen has something to show. */
    private const TOKEN_NAME = 'nuvo-spa';

    public function register(RegisterRequest $request): JsonResponse
    {
        $locale = $request->localeDefaults();

        // One transaction: an account without its business is the broken state
        // the Google flow used to leave behind, and it must not be reachable here.
        $user = DB::transaction(function () use ($request, $locale) {
            $user = User::create([
                'name'            => $request->string('name')->trim()->value(),
                'email'           => $request->string('email')->lower()->trim()->value(),
                'password'        => $request->string('password')->value(),
                'phone'           => $request->string('phone')->trim()->value(),
                'plan'            => User::PLAN_PRO,
                'plan_expires_at' => now()->addDays(User::TRIAL_DAYS),
            ]);

            $user->business()->create([
                'name'                   => $request->string('business_name')->trim()->value(),
                'category'               => $request->string('business_category')->trim()->value(),
                'city'                   => $request->string('business_city')->trim()->value(),
                'country'                => $request->country(),
                'timezone'               => $locale['timezone'],
                'currency'               => $locale['currency'],
                'phone'                  => $request->string('phone')->trim()->value(),
                'whatsapp'               => $request->string('phone')->trim()->value(),
                'working_hours'          => Business::defaultWorkingHours(),
                'slot_duration'          => 30,
                'booking_notice'         => 60,
                'notifications_whatsapp' => true,
                'notifications_email'    => true,
                'notifications_sms'      => false,
                'is_active'              => true,
                'accent_color'           => '#6366f1',
            ]);

            return $user;
        });

        $this->sendWelcome($user);

        return $this->sessionResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', mb_strtolower(trim($validated['email'])))->first();

        // Hash::check is skipped when there is no user, but the message must not
        // differ: "unknown email" and "wrong password" are the same answer here.
        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        return $this->sessionResponse($user);
    }

    /**
     * Sign in with Google, using an access token the SPA obtained client-side.
     *
     * A Google account with no business is a legitimate mid-signup state, so the
     * response says so and the SPA sends the merchant to the setup step. Every
     * dashboard route is guarded by EnsureBusinessExists in the meantime.
     */
    public function googleCallback(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->string('token')->value());
        } catch (\Throwable $e) {
            Log::warning('Google sign-in rejected', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Connexion Google impossible. Réessayez.'], 401);
        }

        $email = mb_strtolower((string) $googleUser->getEmail());

        if ($email === '') {
            return response()->json([
                'message' => 'Votre compte Google ne fournit pas d\'adresse email.',
            ], 422);
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            // Linking an existing password account to Google is intentional: the
            // merchant proved control of the same mailbox we already trust.
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
            ])->save();
        } else {
            $user = User::create([
                'name'            => $googleUser->getName() ?: 'Nouveau commerçant',
                'email'           => $email,
                'google_id'       => $googleUser->getId(),
                'avatar'          => $googleUser->getAvatar(),
                'plan'            => User::PLAN_PRO,
                'plan_expires_at' => now()->addDays(User::TRIAL_DAYS),
            ]);

            $this->sendWelcome($user);
        }

        return $this->sessionResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->userPayload($request->user()->fresh());
    }

    /** Change the merchant's own name, email or phone. */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'min:2', 'max:120'],
            'email' => ['sometimes', 'email:rfc', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['sometimes', 'string', new PhoneNumber()],
        ]);

        if (isset($validated['email'])) {
            $validated['email'] = mb_strtolower(trim($validated['email']));
        }

        $user->update($validated);

        return $this->userPayload($user->fresh());
    }

    /**
     * Change password while signed in.
     *
     * Every other token is revoked afterwards: if the reason for changing it is
     * that somebody else had the old one, they must not keep a live session.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            // Google-only accounts have no password yet, so there is nothing to
            // confirm; they are setting one for the first time.
            'current_password' => [$user->password ? 'required' : 'nullable', 'string'],
            'password'         => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if ($user->password && ! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update(['password' => $validated['password']]);
        $user->tokens()->delete();

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return response()->json([
            'message' => 'Mot de passe mis à jour. Vos autres appareils ont été déconnectés.',
            'token'   => $token,
        ]);
    }

    /**
     * Ask for a reset link.
     *
     * The response never varies with whether the address is registered, and that
     * would turn this endpoint into a membership oracle.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink([
            'email' => mb_strtolower(trim($request->string('email')->value())),
        ]);

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => 'Un email vient déjà d\'être envoyé. Patientez quelques minutes.',
            ], 429);
        }

        return response()->json([
            'message' => 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();

                // A reset is a recovery action: assume the old sessions are not ours.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Ce lien de réinitialisation est invalide ou a expiré.'],
            ]);
        }

        return response()->json(['message' => 'Mot de passe réinitialisé. Vous pouvez vous connecter.']);
    }

    /**
     * Close the account for good.
     *
     * Cascades through business → services → bookings, which is what a deletion
     * request under most privacy regimes actually has to do. Confirmation is
     * required so a stray request cannot trigger it.
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'confirmation'     => ['required', 'in:SUPPRIMER'],
            'current_password' => [$user->password ? 'required' : 'nullable', 'string'],
        ], [
            'confirmation.in' => 'Tapez SUPPRIMER pour confirmer.',
        ]);

        if ($user->password && ! Hash::check($request->string('current_password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe est incorrect.'],
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Votre compte a été supprimé.']);
    }

    // ─── Internals ───────────────────────────────────────────────────────

    /**
     * Issue a token and describe the session.
     *
     * Existing tokens are deliberately left alone: revoking them on every login
     * signed the merchant out of their phone whenever they opened the dashboard
     * on a laptop.
     */
    private function sessionResponse(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return response()->json(
            ['token' => $token] + $this->payloadFor($user),
            $status,
        );
    }

    private function userPayload(User $user, int $status = 200): JsonResponse
    {
        return response()->json($this->payloadFor($user), $status);
    }

    /**
     * What the SPA needs to render a signed-in shell.
     *
     * `plan` is the effective plan, not the stored column: an expired
     * subscription must read as free everywhere, including in the UI.
     */
    private function payloadFor(User $user): array
    {
        $business = $user->business;

        return [
            'user' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone,
                'avatar'          => $user->avatar,
                'plan'            => $user->effectivePlan(),
                'plan_expires_at' => $user->plan_expires_at?->toIso8601String(),
                'on_trial'        => $user->onTrial(),
                'has_password'    => $user->password !== null,
            ],
            'business'    => $business,
            'needs_setup' => $business === null,
        ];
    }

    private function sendWelcome(User $user): void
    {
        try {
            Mail::to($user->email)->queue(new WelcomeNotification($user));
        } catch (\Throwable $e) {
            // Never block a signup because the mail transport is down.
            Log::error('Welcome email could not be queued', [
                'user'  => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
