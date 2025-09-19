<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContropropostaMedico extends Model
{
    use HasFactory;

    protected $table = 'controproposte_medici';

    protected $fillable = [
        'preventivo_paziente_id',
        'medico_user_id',
        'json_proposta',
        'stato',
    ];

    protected $casts = [
        'json_proposta' => 'array',
    ];

    public function preventivoPaziente(): BelongsTo
    {
        return $this->belongsTo(PreventivoPaziente::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_user_id');
    }

    public function anagraficaMedico()
    {
        return $this->hasOneThrough(
            AnagraficaMedico::class,
            User::class,
            'id',
            'user_id',
            'medico_user_id',
            'id'
        );
    }
}
