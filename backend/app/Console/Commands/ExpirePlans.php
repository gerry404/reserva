<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ExpirePlans extends Command
{
    protected $signature = 'plans:expire';
    protected $description = 'Downgrade users with expired plans back to free';

    public function handle(): int
    {
        $expired = User::where('plan', '!=', 'free')
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', now())
            ->get();

        foreach ($expired as $user) {
            $user->update([
                'plan'            => 'free',
                'plan_expires_at' => null,
            ]);
        }

        $this->info("Downgraded {$expired->count()} expired plans to free.");

        return self::SUCCESS;
    }
}
