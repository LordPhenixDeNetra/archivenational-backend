<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\HasUuidPrimaryKey;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuidPrimaryKey;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'phone',
        'first_name',
        'last_name',
        'display_name',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public function passwordCredential()
    {
        return $this->hasOne(PasswordCredential::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withPivot(['assigned_at', 'assigned_by']);
    }

    public function hasPermission(string $code): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($q) use ($code) {
                $q->where('code', $code);
            })
            ->exists();
    }
}
