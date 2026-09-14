<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'primary_goal', 'target_roles_json', 'locations_json', 'remote_preference',
        'career_direction', 'salary_min', 'salary_currency', 'industries_json', 'employment_types_json',
    ];

    protected function casts(): array
    {
        return [
            'target_roles_json' => 'array',
            'locations_json' => 'array',
            'industries_json' => 'array',
            'employment_types_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
