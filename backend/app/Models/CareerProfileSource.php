<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerProfileSource extends Model
{
    use HasFactory;

    protected $fillable = ['career_profile_id', 'source_type', 'source_url', 'source_label', 'retrieved_at', 'metadata_json'];

    protected function casts(): array
    {
        return [
            'retrieved_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function careerProfile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class);
    }
}
