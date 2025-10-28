<?php

namespace App\Models;

use App\Models\User;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Compte extends Model
{
    use HasFactory;
    use HasUuids;

    use SoftDeletes;

    protected $table = 'comptes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'numero_compte',
        'titulaire_compte',
        'type_compte',
        'devise',
        'date_creation',
        'statut_compte',
        'date_debut_blocage',
        'date_fin_blocage',
        'motif_blocage',
    'date_deblocage',
        'version',
        'user_id',
        'client_id',
        'manager_id',
        'is_admin_managed',
        'solde',
        'archived',
        'date_fermeture',
    ];

    protected $casts = [
        'date_creation' => 'datetime',
        'date_debut_blocage' => 'datetime',
        'date_fin_blocage' => 'datetime',
        'date_deblocage' => 'datetime',
        'archived' => 'boolean',
        'date_fermeture' => 'datetime',
        'solde' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('non_archived', function ($query) {
            $query->whereRaw('archived = false');
        });

        static::creating(function ($compte) {
            if (empty($compte->numero_compte)) {
                $compte->numero_compte = static::generateNumero();
            }
            if (array_key_exists('archived', $compte->attributes) || isset($compte->archived)) {
                $val = $compte->attributes['archived'] ?? $compte->archived;
                if (is_bool($val)) {
                    $compte->attributes['archived'] = $val ? 't' : 'f';
                }
            }
        });

        static::updating(function ($compte) {
            if (array_key_exists('archived', $compte->attributes) || isset($compte->archived)) {
                $val = $compte->attributes['archived'] ?? $compte->archived;
                if (is_bool($val)) {
                    $compte->attributes['archived'] = $val ? 't' : 'f';
                }
            }
        });
    }

    public function scopeNumero($query, $numero)
    {
        return $query->where('numero_compte', $numero);
    }

    public function scopeClient($queryOrTelephone, $telephone = null)
    {
        if ($queryOrTelephone instanceof \Illuminate\Database\Eloquent\Builder) {
            $query = $queryOrTelephone;
        } else {
            $query = static::query();
            $telephone = $queryOrTelephone;
        }

        return $query->whereHas('clientRelation', function ($q) use ($telephone) {
            $q->where('telephone', (string) $telephone);
        });
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'compte_id');
    }

    public function clientRelation()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }


    public function getClientAttribute()
    {
        return $this->clientRelation()->getResults();
    }


    public function client()
    {
        return $this->clientRelation();
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function getSoldeAttribute()
    {
        if ($this->transactions()->exists()) {
            $depot = $this->transactions()->where('type', 'depot')->sum('montant');
            $retrait = $this->transactions()->where('type', 'retrait')->sum('montant');
            return $depot - $retrait;
        }

        return $this->attributes['solde'] ?? 0;
    }

    public function scopeEtat($query, $statut)
    {
        return $query->where('statut_compte', $statut);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type_compte', $type);
    }


    public static function generateNumero(): string
    {
        do {
            $numero = 'ACC-'.now()->format('Ymd').'-'.mt_rand(1000, 9999);
        } while (static::where('numero_compte', $numero)->exists());

        return $numero;
    }
}
