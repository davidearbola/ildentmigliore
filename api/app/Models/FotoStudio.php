<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FotoStudio extends Model
{
    use HasFactory;
    protected $table = 'foto_studi';
    protected $fillable = ['medico_user_id', 'file_path'];
    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
