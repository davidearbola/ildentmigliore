<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListinoTipologia extends Model
{
    use HasFactory;

    protected $table = 'listino_tipologie';

    protected $fillable = [
        'nome',
        'descrizione',
    ];

    /**
     * Una tipologia può essere associata a molte voci del listino master.
     */
    public function listinoMasterItems(): HasMany
    {
        return $this->hasMany(ListinoMaster::class, 'id_tipologia');
    }

    /**
     * Una tipologia può essere associata a molte voci del listino medico (master).
     */
    public function listinoMedicoMasterItems(): HasMany
    {
        return $this->hasMany(ListinoMedicoMasterItem::class, 'id_tipologia');
    }

    /**
     * Una tipologia può essere associata a molte voci del listino medico (custom).
     */
    public function listinoMedicoCustomItems(): HasMany
    {
        return $this->hasMany(ListinoMedicoCustomItem::class, 'id_tipologia');
    }
}
