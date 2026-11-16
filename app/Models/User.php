<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'must_change_password', 'recovery_code_hash', 'recovery_code_generated_at'])]
#[Hidden(['password', 'remember_token', 'recovery_code_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'recovery_code_generated_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Generate a readable high-entropy admin recovery code (e.g. BBH-XXXX-XXXX-XXXX).
     */
    public static function generateRecoveryCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $maxIndex = strlen($alphabet) - 1;
        $groups = [];

        for ($g = 0; $g < 3; $g++) {
            $group = '';
            for ($i = 0; $i < 4; $i++) {
                $group .= $alphabet[random_int(0, $maxIndex)];
            }
            $groups[] = $group;
        }

        return 'BBH-'.implode('-', $groups);
    }

    /**
     * Normalize user input for recovery code comparison (uppercase, trimmed, hyphens preserved).
     */
    public static function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($code)) ?? '');
    }
}
