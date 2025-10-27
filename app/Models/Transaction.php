<?php

namespace App\Models;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'account_transactions';
    protected $keyType = 'int';
    public $incrementing = true;
    protected $fillable = [
        'montant', 'type', 'compte_id', 'archived'
    ];

    protected $casts = [
        'archived' => 'boolean',
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }
}
