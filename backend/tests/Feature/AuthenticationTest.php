<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
    }

    public function test_registration_creates_a_user_and_their_business_together(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Awa Ndiaye',
            'email'                 => 'Awa@Example.com',
            'password'              => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'phone'                 => '+221771234567',
            'business_name'         => 'Chez Awa',
            'business_category'     => 'Coiffure',
            'business_city'         => 'Dakar',
            'business_country'      => 'SN',
        ]);

        $response->assertCreated()
            ->assertJsonPath('needs_setup', false)
            ->assertJsonStructure(['token', 'user', 'business']);

        $user = User::firstOrFail();

        // Emails are identity: stored lowercase so nobody registers twice.
        $this->assertSame('awa@example.com', $user->email);
        $this->assertNotNull($user->business);
        $this->assertSame('Africa/Dakar', $user->business->timezone);
        $this->assertSame('XOF', $user->business->currency);

        // Every account starts on the Pro trial.
        $this->assertSame(User::PLAN_PRO, $user->effectivePlan());
        $this->assertTrue($user->onTrial());
    }

    public function test_a_weak_password_is_refused(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Awa', 'email' => 'a@b.com',
            'password' => 'abc', 'password_confirmation' => 'abc',
            'phone' => '+237691234567', 'business_name' => 'X',
            'business_category' => 'Y', 'business_city' => 'Z',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_registration_rolls_back_entirely_if_the_business_cannot_be_created(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Awa', 'email' => 'a@b.com',
            'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123',
            'phone' => 'pas-un-numero', 'business_name' => 'X',
            'business_category' => 'Y', 'business_city' => 'Z',
        ])->assertStatus(422);

        $this->assertSame(0, User::count());
    }

    public function test_a_wrong_password_and_an_unknown_email_give_the_same_answer(): void
    {
        User::factory()->create(['email' => 'known@example.com']);

        $wrongPassword = $this->postJson('/api/auth/login', [
            'email' => 'known@example.com', 'password' => 'incorrect',
        ]);
        $unknownEmail = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com', 'password' => 'incorrect',
        ]);

        // Identical replies: this endpoint must not reveal who has an account.
        $wrongPassword->assertStatus(422);
        $unknownEmail->assertStatus(422);
        $this->assertSame($wrongPassword->json('errors'), $unknownEmail->json('errors'));
    }

    public function test_signing_in_does_not_sign_the_merchant_out_elsewhere(): void
    {
        $user = User::factory()->create(['email' => 'awa@example.com']);

        $phone = $this->postJson('/api/auth/login', [
            'email' => 'awa@example.com', 'password' => 'password',
        ])->json('token');

        $this->postJson('/api/auth/login', [
            'email' => 'awa@example.com', 'password' => 'password',
        ])->assertOk();

        // The phone's token is still good after logging in on a laptop.
        $this->withToken($phone)->getJson('/api/auth/me')->assertOk();
        $this->assertSame(2, $user->tokens()->count());
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'target@example.com']);

        foreach (range(1, 5) as $ignored) {
            $this->postJson('/api/auth/login', [
                'email' => 'target@example.com', 'password' => 'guess',
            ]);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'target@example.com', 'password' => 'guess',
        ])->assertStatus(429);
    }

    // ─── Password recovery ───────────────────────────────────────────────

    public function test_a_reset_link_is_sent_and_points_at_the_frontend(): void
    {
        $user = User::factory()->create(['email' => 'awa@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'awa@example.com'])
            ->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_contains($mail->actionUrl, config('app.frontend_url') . '/reset-password');
        });
    }

    public function test_asking_about_an_unknown_email_reveals_nothing(): void
    {
        $known = $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com']);

        $known->assertOk();
        Notification::assertNothingSent();
    }

    public function test_a_reset_sets_the_new_password_and_revokes_old_sessions(): void
    {
        $user  = User::factory()->create(['email' => 'awa@example.com']);
        $token = Password::createToken($user);
        $user->createToken('old-device');

        $this->postJson('/api/auth/reset-password', [
            'token'                 => $token,
            'email'                 => 'awa@example.com',
            'password'              => 'nouveaupass123',
            'password_confirmation' => 'nouveaupass123',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('nouveaupass123', $user->password));
        // A reset is a recovery action: assume the old sessions were not ours.
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_an_invalid_reset_token_is_refused(): void
    {
        User::factory()->create(['email' => 'awa@example.com']);

        $this->postJson('/api/auth/reset-password', [
            'token'                 => 'forged',
            'email'                 => 'awa@example.com',
            'password'              => 'nouveaupass123',
            'password_confirmation' => 'nouveaupass123',
        ])->assertStatus(422);
    }

    // ─── Account management ──────────────────────────────────────────────

    public function test_changing_the_password_requires_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/auth/password', [
            'current_password'      => 'wrong',
            'password'              => 'nouveaupass123',
            'password_confirmation' => 'nouveaupass123',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');
    }

    public function test_deleting_an_account_removes_everything_it_owned(): void
    {
        $user     = User::factory()->create();
        $business = Business::factory()->for($user, 'user')->create();
        $service  = Service::factory()->for($business)->create();
        $booking  = Booking::factory()->for($business)->for($service)->create();

        $this->actingAs($user)->deleteJson('/api/auth/account', [
            'confirmation'     => 'SUPPRIMER',
            'current_password' => 'password',
        ])->assertOk();

        // Asserted per row, not on table counts: factories build their own
        // fixtures alongside these, and a count would be measuring those too.
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('businesses', ['id' => $business->id]);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_deleting_an_account_needs_an_explicit_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/auth/account', [
            'confirmation'     => 'oui',
            'current_password' => 'password',
        ])->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
