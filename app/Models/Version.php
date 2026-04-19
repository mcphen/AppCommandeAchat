<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $fillable = [
        'versionable_type', 'versionable_id', 'numero', 'data',
    ];

    public function versionable()
    {
        return $this->morphTo();
    }
}
