<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnagraficaPaziente extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anagrafica_pazienti';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'cellulare',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'lat',
        'lng',
    ];

    /**
     * Get the user that owns the anagrafica.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the preventivi for the anagrafica.
     */
    public function preventivi(): HasMany
    {
        return $this->hasMany(PreventivoPaziente::class);
    }
}
