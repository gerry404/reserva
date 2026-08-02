<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function view(User $user, Service $service): bool
    {
        return $this->owns($user, $service);
    }

    public function update(User $user, Service $service): bool
    {
        return $this->owns($user, $service);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->owns($user, $service);
    }

    private function owns(User $user, Service $service): bool
    {
        return $user->business !== null
            && $service->business_id === $user->business->id;
    }
}
