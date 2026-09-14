<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = ['career_profile_id', 'company', 'title', 'start_date', 'end_date', 'is_current', 'description'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function careerProfile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class);
    }
}
