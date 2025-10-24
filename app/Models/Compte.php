<?php

namespace App\Models;

use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Compte extends Model
{
    use HasFactory;

    protected $table = 'comptes';
    // use integer autoincrement id to match the database migration
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'numero_compte',
        'titulaire_compte',
        'type_compte',
        'devise',
        'date_creation',
        'statut_compte',
        'motif_blocage',
        'version',
        'user_id',
        'client_id',
        'solde',
        'archived',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'archived' => 'boolean',
        'solde' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('non_archived', function ($query) {
            $query->where('archived', false);
        });
    }

    // Scope pour rechercher par numéro de compte
    public function scopeNumero($query, $numero)
    {
        return $query->where('numero_compte', $numero);
    }

    // Scope pour rechercher les comptes liés à un client (par téléphone)
    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('client', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'compte_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function getSoldeAttribute()
    {
        $depot = $this->transactions()->where('type', 'depot')->sum('montant');
        $retrait = $this->transactions()->where('type', 'retrait')->sum('montant');

        return $depot - $retrait;
    }

    public function scopeEtat($query, $statut)
    {
        return $query->where('statut_compte', $statut);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type_compte', $type);
    }
}
