<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'linkedin_url', 'linkedin_url_claim_status', 'research_status', 'research_error_code',
        'headline', 'location', 'current_role', 'current_company', 'professional_summary', 'professional_focus',
        'completeness_score', 'confirmed_at', 'last_researched_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'last_researched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(CareerProfileSource::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'career_profile_skill')
            ->withPivot(['proficiency', 'evidence_level'])
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
