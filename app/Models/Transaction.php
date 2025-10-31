<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Compte;
use App\Models\User;

class Transaction extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code_transaction', 'type', 'montant', 'description', 'compte_id', 'agent_id'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
