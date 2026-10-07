<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivity extends Model
{
    protected $fillable = [
        'actor_id',
        'activity_type',
        'description',
    ];

    public static function record(string $activityType, string $description): self
    {
        return static::query()->create([
            'actor_id' => auth('admin')->id(),
            'activity_type' => $activityType,
            'description' => $description,
        ]);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
