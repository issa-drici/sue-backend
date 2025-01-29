<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Entities\User;

class UserModel extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $table = 'users';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function toEntity(): User
    {
        return new User(
            id: $this->id,
            fullName: $this->full_name,
            email: $this->email,
            phone: $this->phone,
            role: $this->role,
        );
    }

    public static function fromEntity(User $user): self
    {
        return new self([
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'role' => $user->getRole(),
        ]);
    }
} 