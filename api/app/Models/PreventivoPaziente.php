<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreventivoPaziente extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'preventivi_pazienti';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'anagrafica_paziente_id',
        'file_path',
        'file_name_originale',
        'stato_elaborazione',
        'json_preventivo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'json_preventivo' => 'array',
    ];

    /**
     * Get the anagrafica that owns the preventivo.
     */
    public function anagraficaPaziente(): BelongsTo
    {
        return $this->belongsTo(AnagraficaPaziente::class);
    }

    public function controproposte()
    {
        return $this->hasMany(ContropropostaMedico::class);
    }
}
