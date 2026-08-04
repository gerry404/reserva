<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ce qui compte comme un essai.
 *
 * Le libellé « Essai Pro · N j » de l'en-tête se lit directement de là. Sans la
 * borne sur la durée, un compte Pro offert pour un an (démonstration,
 * partenariat) affichait « Essai Pro · 365 j », ce qui est faux et laisse
 * croire à une échéance imminente.
 */
class TrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_account_is_on_trial(): void
    {
        $user = User::factory()->create([
            'plan' => User::PLAN_PRO,
            'plan_expires_at' => now()->addDays(User::TRIAL_DAYS),
        ]);

        $this->assertTrue($user->onTrial());
    }

    public function test_a_long_grant_is_not_a_trial(): void
    {
        $user = User::factory()->create([
            'plan' => User::PLAN_PRO,
            'plan_expires_at' => now()->addYear(),
        ]);

        $this->assertFalse($user->onTrial());
        // Toujours Pro, en revanche : c'est bien un abonnement, pas un essai.
        $this->assertTrue($user->isPro());
    }

    public function test_paying_ends_the_trial(): void
    {
        $user = User::factory()->create([
            'plan' => User::PLAN_PRO,
            'plan_expires_at' => now()->addDays(10),
        ]);

        Payment::create([
            'user_id' => $user->id,
            'tx_ref' => Payment::generateTxRef(),
            'plan' => User::PLAN_PRO,
            'billing_cycle' => Payment::CYCLE_MONTHLY,
            'amount' => 2900,
            'currency' => 'XAF',
            'status' => Payment::STATUS_SUCCESSFUL,
            'paid_at' => now(),
        ]);

        $this->assertFalse($user->fresh()->onTrial());
    }

    public function test_a_free_account_is_not_on_trial(): void
    {
        $this->assertFalse(User::factory()->create()->onTrial());
    }
}
