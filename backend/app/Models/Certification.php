<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = ['career_profile_id', 'name', 'issuer', 'issue_date', 'expiration_date', 'credential_url'];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    public function careerProfile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class);
    }
}
