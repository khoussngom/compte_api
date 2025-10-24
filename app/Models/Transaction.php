<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'montant', 'type', 'compte_id'
    ];

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }
}
