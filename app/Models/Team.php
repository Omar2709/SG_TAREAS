<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'owner_id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function roleOf(User|int|string|null $user): ?string
    {
        $userId = $this->resolveUserId($user);

        if ($userId === null) {
            return null;
        }

        if ($this->owner_id === $userId) {
            return 'owner';
        }

        if ($this->relationLoaded('members')) {
            return $this->members->firstWhere('user_id', $userId)?->role;
        }

        return $this->members()
            ->where('user_id', $userId)
            ->value('role');
    }

    public function hasMember(User|int|string|null $user): bool
    {
        return $this->roleOf($user) !== null;
    }

    public function userCanManage(User|int|string|null $user): bool
    {
        return in_array($this->roleOf($user), ['owner', 'admin'], true);
    }

    public function userIsOwner(User|int|string|null $user): bool
    {
        return $this->roleOf($user) === 'owner';
    }

    private function resolveUserId(User|int|string|null $user): ?int
    {
        if ($user instanceof User) {
            return $user->id;
        }

        if (is_int($user)) {
            return $user;
        }

        if (is_string($user) && ctype_digit($user)) {
            return (int) $user;
        }

        return null;
    }
}
