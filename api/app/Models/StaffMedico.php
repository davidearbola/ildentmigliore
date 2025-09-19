<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StaffMedico extends Model
{
    use HasFactory;
    protected $table = 'staff_medici';
    protected $fillable = [
        'medico_user_id',
        'nome',
        'ruolo',
        'specializzazione',
        'esperienza',
        'foto_path'
    ];
    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        // Usiamo 'foto_path' come nome della colonna
        return Storage::disk('public')->url($this->foto_path);
    }
}
