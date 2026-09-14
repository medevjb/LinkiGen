<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldProvenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type', 'entity_id', 'field_name', 'authority', 'confidence',
        'source_id', 'original_value_json', 'set_at',
    ];

    protected function casts(): array
    {
        return [
            'original_value_json' => 'array',
            'set_at' => 'datetime',
        ];
    }

    // Authority ordering per ADR-004: user > cv > research > inference
    public const AUTHORITY_RANK = [
        'user' => 3,
        'cv' => 2,
        'research' => 1,
        'inference' => 0,
    ];

    public function outranks(string $otherAuthority): bool
    {
        return self::AUTHORITY_RANK[$this->authority] >= self::AUTHORITY_RANK[$otherAuthority];
    }
}
