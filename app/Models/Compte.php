
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
    protected $keyType = 'string';
    public $incrementing = false;

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
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
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
