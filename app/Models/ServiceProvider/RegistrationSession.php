<?php

namespace App\Models\ServiceProvider;

use Illuminate\Database\Eloquent\Model;

class RegistrationSession extends Model
{
    protected $fillable = [
        'token',
        'step',
        'data',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function findByToken(string $token): ?self
    {
        $session = static::where('token', $token)->first();

        if ($session && $session->isExpired()) {
            $session->delete();
            return null;
        }

        return $session;
    }
}