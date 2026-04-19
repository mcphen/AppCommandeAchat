<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escalade extends Model
{
    protected $fillable = [
        'approval_step_id', 'user_id', 'delai_jours',
    ];

    public function step()
    {
        return $this->belongsTo(ApprovalStep::class, 'approval_step_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
