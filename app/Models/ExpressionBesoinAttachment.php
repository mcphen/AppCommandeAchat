<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpressionBesoinAttachment extends Model
{
    protected $table = 'expression_besoin_attachments';

    protected $fillable = ['expression_besoin_id', 'nom_fichier', 'chemin', 'type_mime', 'taille'];

    public function expressionBesoin(): BelongsTo
    {
        return $this->belongsTo(ExpressionBesoin::class);
    }
}
