<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findByEmailOrTelephone(?string $email, ?string $telephone): ?User
    {
        return User::when($email, fn($q) => $q->orWhere('email', $email))
            ->when($telephone, fn($q) => $q->orWhere('telephone', $telephone))
            ->first();
    }

    public function findByIdentifier(string $identifier): ?User
    {
        return User::where('email', $identifier)
            ->orWhere('telephone', $identifier)
            ->first();
    }

    public function save(User $user): User
    {
        $user->save();
        return $user->fresh();
    }
}
