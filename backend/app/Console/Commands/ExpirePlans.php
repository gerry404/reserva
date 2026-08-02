<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Write expired subscriptions back to the free plan.
 *
 * This is housekeeping, not enforcement. Access is decided by
 * User::effectivePlan(), which already treats a past expiry as free — so a day
 * when this job does not run costs nobody anything, and no merchant keeps paid
 * features by virtue of a missed cron. Keeping the column truthful just means
 * reporting and support see the same state the app enforces.
 */
class ExpirePlans extends Command
{
    protected $signature = 'plans:expire';

    protected $description = 'Reset lapsed subscriptions to the free plan';

    public function handle(): int
    {
        $expired = User::query()
            ->where('plan', '!=', User::PLAN_FREE)
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', now())
            ->update([
                'plan'            => User::PLAN_FREE,
                'plan_expires_at' => null,
            ]);

        $this->info("{$expired} abonnement(s) expiré(s) repassé(s) en Gratuit.");

        return self::SUCCESS;
    }
}
