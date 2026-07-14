<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'teacher_id', 'published', 'available_from', 'available_until'])]
class Course extends Model
{
    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** Published and, if set, inside its availability window. */
    public function isAvailableNow(): bool
    {
        if (! $this->published) {
            return false;
        }

        $now = now();

        return (! $this->available_from || $now->gte($this->available_from))
            && (! $this->available_until || $now->lte($this->available_until));
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class)->orderBy('position');
    }
}
