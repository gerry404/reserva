<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who may reach what.
 *
 * Three separable questions, all of which used to be answered wrongly
 * somewhere: is the caller signed in, do they own the row, and have they paid
 * for the feature.
 */
class MerchantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_routes_require_authentication(): void
    {
        $this->getJson('/api/bookings')->assertUnauthorized();
        $this->getJson('/api/dashboard/stats')->assertUnauthorized();
        $this->getJson('/api/business')->assertUnauthorized();
    }

    public function test_a_merchant_cannot_read_another_merchants_booking(): void
    {
        $intruder = $this->merchant();
        $victim   = $this->merchant();

        $booking = Booking::factory()->for($victim->business)->create();

        $this->actingAs($intruder)->getJson("/api/bookings/{$booking->id}")->assertForbidden();
        $this->actingAs($intruder)->patchJson("/api/bookings/{$booking->id}/status", [
            'status' => Booking::STATUS_CONFIRMED,
        ])->assertForbidden();
        $this->actingAs($intruder)->deleteJson("/api/bookings/{$booking->id}")->assertForbidden();
    }

    public function test_a_merchant_cannot_modify_another_merchants_service(): void
    {
        $intruder = $this->merchant();
        $victim   = $this->merchant();

        $service = Service::factory()->for($victim->business)->create();

        $this->actingAs($intruder)->putJson("/api/services/{$service->id}", ['name' => 'Volé'])
            ->assertForbidden();
        $this->actingAs($intruder)->deleteJson("/api/services/{$service->id}")
            ->assertForbidden();

        $this->assertNotSame('Volé', $service->fresh()->name);
    }

    public function test_the_bookings_list_only_contains_the_merchants_own(): void
    {
        $merchant = $this->merchant();
        $other    = $this->merchant();

        Booking::factory()->count(3)->for($merchant->business)->create();
        Booking::factory()->count(5)->for($other->business)->create();

        $this->actingAs($merchant)->getJson('/api/bookings')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ─── Business setup gate ─────────────────────────────────────────────

    /**
     * Signing up with Google leaves an account with no business. Reaching for
     * ->business->id used to be a fatal error on that account's first request.
     */
    public function test_an_account_without_a_business_is_told_to_finish_setup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/dashboard/stats')
            ->assertStatus(409)
            ->assertJsonPath('needs_setup', true);
    }

    public function test_setup_creates_the_business_and_unblocks_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/business/setup', [
            'name'     => 'Chez Awa',
            'category' => 'Coiffure',
            'city'     => 'Dakar',
            'country'  => 'SN',
            'phone'    => '+221771234567',
        ])->assertCreated();

        $business = $user->fresh()->business;
        $this->assertNotNull($business);
        // Country drives the clock and the currency.
        $this->assertSame('Africa/Dakar', $business->timezone);
        $this->assertSame('XOF', $business->currency);

        $this->actingAs($user->fresh())->getJson('/api/dashboard/stats')->assertOk();
    }

    public function test_setup_cannot_be_run_twice(): void
    {
        $merchant = $this->merchant();

        $this->actingAs($merchant)->postJson('/api/business/setup', [
            'name' => 'Doublon', 'category' => 'X', 'city' => 'Y', 'phone' => '+237691234567',
        ])->assertStatus(409);
    }

    // ─── Paid features ───────────────────────────────────────────────────

    public function test_advanced_analytics_are_closed_to_free_accounts(): void
    {
        $merchant = $this->merchant();

        $this->actingAs($merchant)->getJson('/api/dashboard/analytics')
            ->assertStatus(402)
            ->assertJsonPath('required_plan', User::PLAN_PRO);
    }

    public function test_advanced_analytics_are_open_to_pro_accounts(): void
    {
        $merchant = $this->merchant(User::factory()->pro());

        $this->actingAs($merchant)->getJson('/api/dashboard/analytics')->assertOk();
    }

    public function test_an_expired_subscription_loses_paid_features_immediately(): void
    {
        // No nightly job has run; entitlement is computed, so access is already gone.
        $merchant = $this->merchant(User::factory()->expired());

        $this->actingAs($merchant)->getJson('/api/dashboard/analytics')->assertStatus(402);
    }

    public function test_a_custom_link_is_reserved_for_pro(): void
    {
        $merchant = $this->merchant();

        $this->actingAs($merchant)->putJson('/api/business', ['slug' => 'mon-super-salon'])
            ->assertStatus(402);

        $this->actingAs($this->merchant(User::factory()->pro()))
            ->putJson('/api/business', ['slug' => 'mon-super-salon'])
            ->assertOk();
    }

    public function test_reserved_slugs_are_refused(): void
    {
        $merchant = $this->merchant(User::factory()->pro());

        $this->actingAs($merchant)->putJson('/api/business', ['slug' => 'dashboard'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    public function test_basic_stats_stay_available_on_the_free_plan(): void
    {
        $merchant = $this->merchant();

        $this->actingAs($merchant)->getJson('/api/dashboard/stats')->assertOk();
        $this->actingAs($merchant)->getJson('/api/dashboard/chart')->assertOk();
    }

    private function merchant(?\Illuminate\Database\Eloquent\Factories\Factory $userFactory = null): User
    {
        $user = ($userFactory ?? User::factory())->create();
        Business::factory()->for($user, 'user')->create();

        return $user->fresh();
    }
}
