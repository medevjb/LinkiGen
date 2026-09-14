<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'normalized_name'];

    public function careerProfiles(): BelongsToMany
    {
        return $this->belongsToMany(CareerProfile::class, 'career_profile_skill')
            ->withPivot(['proficiency', 'evidence_level'])
            ->withTimestamps();
    }
}
