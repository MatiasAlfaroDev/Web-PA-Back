<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['challenge_id', 'stdin', 'expected_output', 'is_hidden'])]
class TestCase extends Model
{
    protected function casts(): array
    {
        return ['is_hidden' => 'boolean'];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
