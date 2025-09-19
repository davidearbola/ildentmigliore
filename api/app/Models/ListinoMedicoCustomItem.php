<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListinoMedicoCustomItem extends Model
{
    use HasFactory;

    protected $table = 'listino_medico_custom_items';

    protected $fillable = [
        'medico_user_id',
        'nome',
        'descrizione',
        'prezzo',
        'id_tipologia',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_user_id');
    }
    /**
     * Ogni voce del listino medico (custom) appartiene a una tipologia.
     */
    public function tipologia(): BelongsTo
    {
        return $this->belongsTo(ListinoTipologia::class, 'id_tipologia');
    }
}
