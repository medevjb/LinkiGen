<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'weekly_plan_id', 'user_id', 'type', 'title', 'description', 'priority',
        'scheduled_for', 'due_at', 'estimated_minutes', 'automation_level',
        'approval_required', 'status', 'fingerprint', 'rationale', 'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'due_at' => 'datetime',
            'approval_required' => 'boolean',
            'metadata_json' => 'array',
        ];
    }

    public function weeklyPlan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
