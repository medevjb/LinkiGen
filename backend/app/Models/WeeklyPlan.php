<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyPlan extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'week_start', 'week_end', 'timezone', 'status', 'generated_at'];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
