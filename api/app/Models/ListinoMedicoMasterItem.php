<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Aggiungi questo import

class ListinoMedicoMasterItem extends Model
{
    use HasFactory;

    protected $table = 'listino_medico_master_items';

    protected $fillable = [
        'medico_user_id',
        'listino_master_id',
        'prezzo',
        'is_active',
        'id_tipologia',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_user_id');
    }

    public function voceMaster(): BelongsTo
    {
        return $this->belongsTo(ListinoMaster::class, 'listino_master_id');
    }

    public function tipologia(): BelongsTo
    {
        return $this->belongsTo(ListinoTipologia::class, 'id_tipologia');
    }
}
