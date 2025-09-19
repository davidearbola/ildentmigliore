<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListinoMaster extends Model
{
    use HasFactory;

    protected $table = 'listino_master';

    protected $fillable = [
        'nome',
        'descrizione',
        'is_active',
        'id_tipologia'
    ];

    public function tipologia(): BelongsTo
    {
        return $this->belongsTo(ListinoTipologia::class, 'id_tipologia');
    }
}