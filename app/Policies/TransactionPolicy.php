<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function manage(User $user)
    {
        return (bool) ($user->admin);
    }

    public function view(User $user, Transaction $transaction)
    {
        if ($user->admin) {
            return true;
        }

        // client: can view if owns the compte
        return optional($transaction->compte->client)->user_id === $user->id;
    }
}
