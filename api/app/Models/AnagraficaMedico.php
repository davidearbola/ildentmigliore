<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnagraficaMedico extends Model
{
    protected $table = 'anagrafica_medici';

    protected $fillable = [
        'ragione_sociale',
        'p_iva',
        'cellulare',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'descrizione',
        'lat',
        'lng',
        'user_id',
        'step_listino_completed_at',
        'step_profilo_completed_at',
        'step_staff_completed_at',
        'tipo_registrazione'
    ];

    protected $casts = [
        'step_listino_completed_at' => 'datetime',
        'step_profilo_completed_at' => 'datetime',
        'step_staff_completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
