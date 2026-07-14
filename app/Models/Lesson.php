<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'title', 'content', 'position', 'published', 'available_from', 'available_until'])]
class Lesson extends Model
{
    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
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

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class)->orderBy('position');
    }
}
