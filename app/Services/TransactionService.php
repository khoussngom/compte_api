<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function listForUser(User $user)
    {
        if ($user->admin) {
            return Transaction::query()->with('compte','agent')->latest();
        }

        // client: transactions for comptes owned by this user
        return Transaction::whereHas('compte.client', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('compte','agent')->latest();
    }

    public function find(string $id)
    {
        return Transaction::with('compte','agent')->find($id);
    }

    public function create(array $data, User $agent)
    {
        return DB::transaction(function () use ($data, $agent) {
            $compte = Compte::lockForUpdate()->findOrFail($data['compte_id']);

            if (!empty($compte->archived) || method_exists($compte, 'trashed') && $compte->trashed()) {
                throw new \Exception('Impossible de faire une transaction sur un compte archivé ou supprimé');
            }

            $type = strtolower($data['type']);

            if ($type === 'retrait') {
                $isEpargne = strtolower((string)($compte->type_compte ?? $compte->type ?? '')) === 'epargne';
                $isBloque = strtolower((string)($compte->statut_compte ?? '')) === 'bloqué';
                if ($isEpargne && $isBloque) {
                    throw new \Exception('Retrait interdit sur compte épargne bloqué');
                }

                // calculate solde
                $depot = $compte->transactions()->where('type', 'depot')->sum('montant');
                $retrait = $compte->transactions()->where('type', 'retrait')->sum('montant');
                $solde = $depot - $retrait;

                if ($data['montant'] > $solde) {
                    throw new \Exception('Solde insuffisant');
                }
            }

            $transaction = Transaction::create([
                'code_transaction' => 'TD' . now()->format('YmdHis') . substr(uniqid(), -4),
                'type' => $type,
                'montant' => $data['montant'],
                'description' => $data['description'] ?? null,
                'compte_id' => $compte->id,
                'agent_id' => $agent->id,
            ]);

            return $transaction->load('compte','agent');
        });
    }

    public function update(Transaction $transaction, array $data)
    {
        return DB::transaction(function () use ($transaction, $data) {
            // naive update: only allow change of montant/description/type with same validations
            if (isset($data['type'])) {
                $transaction->type = strtolower($data['type']);
            }
            if (isset($data['montant'])) {
                // if becomes retrait, validate balance
                if ($transaction->type === 'retrait') {
                    $compte = Compte::lockForUpdate()->findOrFail($transaction->compte_id);
                    $depot = $compte->transactions()->where('type', 'depot')->sum('montant');
                    $retrait = $compte->transactions()->where('type', 'retrait')->sum('montant');
                    $solde = $depot - $retrait + $transaction->montant; // add back old montant
                    if ($data['montant'] > $solde) {
                        throw new \Exception('Solde insuffisant pour la modification');
                    }
                }
                $transaction->montant = $data['montant'];
            }
            if (array_key_exists('description', $data)) {
                $transaction->description = $data['description'];
            }

            $transaction->save();
            return $transaction->fresh('compte','agent');
        });
    }

    public function destroy(Transaction $transaction)
    {
        return DB::transaction(function () use ($transaction) {
            $transaction->delete();
            return true;
        });
    }

    // Dashboard helpers
    public function globalDashboard()
    {
        $totalDepot = Transaction::where('type', 'depot')->sum('montant');
        $totalRetrait = Transaction::where('type', 'retrait')->sum('montant');
        $count = Transaction::count();
        $last = Transaction::with('compte','agent')->latest()->first();
        $totalComptes = Compte::count();
        $soldeGlobal = $totalDepot - $totalRetrait;
        $latest10 = Transaction::with('compte','agent')->latest()->take(10)->get();
        $comptesToday = Compte::whereDate('created_at', now()->toDateString())->get();

        return compact('totalDepot','totalRetrait','count','last','totalComptes','soldeGlobal','latest10','comptesToday');
    }

    public function personalDashboard(User $user)
    {
        $transactions = Transaction::whereHas('compte.client', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        $totalDepot = (clone $transactions)->where('type','depot')->sum('montant');
        $totalRetrait = (clone $transactions)->where('type','retrait')->sum('montant');
        $count = (clone $transactions)->count();
        $balance = $totalDepot - $totalRetrait;
        $latest10 = (clone $transactions)->latest()->take(10)->get();
        $comptes = Compte::whereHas('client', function ($q) use ($user) { $q->where('user_id', $user->id); })->get();

        return compact('totalDepot','totalRetrait','count','balance','latest10','comptes');
    }
}
