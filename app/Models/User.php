<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'role_label',
        'focus',
        'timezone',
        'status',
        'avatar_url',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assignee_user_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members', 'user_id', 'project_id')->withPivot('role', 'joined_at');
    }

    public static function normalizeSlug(?string $value): string
    {
        $slug = strtolower(trim((string) $value));
        $slug = preg_replace('/\s+/', '', $slug) ?? '';

        if ($slug === '') {
            return '';
        }

        return '@' . ltrim($slug, '@');
    }

    public static function slugVariants(?string $value): array
    {
        $normalized = static::normalizeSlug($value);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_unique([
            $normalized,
            ltrim($normalized, '@'),
        ]));
    }

    public static function findBySlug(?string $value): ?self
    {
        $variants = static::slugVariants($value);

        if ($variants === []) {
            return null;
        }

        return static::query()
            ->whereIn('slug', $variants)
            ->first();
    }

    public static function slugExists(?string $value, ?string $ignoreUserId = null): bool
    {
        $variants = static::slugVariants($value);

        if ($variants === []) {
            return false;
        }

        $query = static::query()->whereIn('slug', $variants);

        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->exists();
    }
}
