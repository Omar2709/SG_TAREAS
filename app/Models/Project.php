<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'created_by', 'name', 'description', 'status', 'started_at', 'completed_at'];

    protected $casts = [
        'status' => ProjectStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => ProjectStatus::Active->value,
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isArchived(): bool
    {
        return $this->status === ProjectStatus::Archived;
    }

    public function transitionTo(ProjectStatus $status): void
    {
        $this->status = $status;

        if ($status === ProjectStatus::Completed) {
            $this->completed_at ??= now();

            return;
        }

        if ($status === ProjectStatus::Active) {
            $this->completed_at = null;
        }
    }
}
