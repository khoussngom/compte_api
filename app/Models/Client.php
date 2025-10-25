<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Compte;

class Client extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'nom', 'prenom', 'email', 'telephone', 'date_naissance', 'user_id', 'adresse', 'nci'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function comptes()
    {
        return $this->hasMany(Compte::class, 'client_id');
    }
}
