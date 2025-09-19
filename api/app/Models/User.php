<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail

{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'auth_provider_id',
        'auth_provider',
        'name',
        'role',
        'email',
        'password',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * 
     * Invia la notifica di verifica email personalizzata.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }

    public function anagraficaMedico()
    {
        return $this->hasOne(AnagraficaMedico::class);
    }

    public function anagraficaPaziente()
    {
        return $this->hasOne(AnagraficaPaziente::class);
    }

    public function listinoMasterItems()
    {
        return $this->hasMany(ListinoMedicoMasterItem::class, 'medico_user_id');
    }

    public function listinoCustomItems()
    {
        return $this->hasMany(ListinoMedicoCustomItem::class, 'medico_user_id');
    }

    public function fotoStudi()
    {
        return $this->hasMany(FotoStudio::class, 'medico_user_id');
    }

    public function staff()
    {
        return $this->hasMany(StaffMedico::class, 'medico_user_id');
    }

    public function notifiche()
    {
        return $this->hasMany(Notifica::class);
    }

    public function controproposte()
    {
        return $this->hasMany(ContropropostaMedico::class, 'medico_user_id');
    }
}
