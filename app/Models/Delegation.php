<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delegation extends Model
{
    protected $fillable = [
        'delegant_id', 'delegataire_id', 'date_debut', 'date_fin', 'scope',
    ];

    public function delegant()
    {
        return $this->belongsTo(User::class, 'delegant_id');
    }

    public function delegataire()
    {
        return $this->belongsTo(User::class, 'delegataire_id');
    }
}
