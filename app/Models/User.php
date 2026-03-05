<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Notifications\AdminResetPasswordNotification;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    use TwoFactorAuthenticatable;

    use HasRoles;

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'middle_name',
        'gender',
        'address',
        'email',
        'password',
        'role',
        'status',
        'failed_attempts',
    'locked_until',
    'last_failed_login',
    'lock_level',

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

   protected $casts = [
        'email_verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_failed_login' => 'datetime',
        'password' => 'hashed', // ← Automatic bcrypt hashing here
    ];

    // Relationships
    public function logs()
    {
        return $this->hasMany(Log::class, 'user_id', 'user_id');
    }

    // Generate custom user_id on creating
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($user) {
            if (empty($user->user_id)) {
                $prefix = $user->role === 'admin' ? 'A' : ($user->role === 'staff' ? 'S' : 'U');
                $last = self::where('user_id', 'like', $prefix . '%')->orderByDesc('user_id')->first();
                $num = 1;
                if ($last) {
                    $num = intval(substr($last->user_id, 1)) + 1;
                }
                $user->user_id = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
            }
        });
    }
public function sendPasswordResetNotification($token)
{
    $this->notify(new AdminResetPasswordNotification($token));
}

}
