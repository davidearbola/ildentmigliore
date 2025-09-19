<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifica extends Model
{
    use HasFactory;

    protected $table = 'notifiche';

    protected $fillable = [
        'user_id',
        'tipo',
        'messaggio',
        'url_azione',
        'letta_at',
    ];

    protected $casts = [
        'letta_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
