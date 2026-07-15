<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Singleton row (id = 1). No row / null / past locked_until = unlocked. */
#[Fillable(['id', 'locked_until'])]
class SiteLock extends Model
{
    protected function casts(): array
    {
        return ['locked_until' => 'datetime'];
    }

    /** Current lock expiry if still in the future, else null. */
    public static function activeUntil(): ?Carbon
    {
        $until = static::find(1)?->locked_until;

        return $until && $until->isFuture() ? $until : null;
    }

    public static function set(Carbon $until): self
    {
        return static::updateOrCreate(['id' => 1], ['locked_until' => $until]);
    }

    public static function clear(): void
    {
        static::whereKey(1)->delete();
    }
}
