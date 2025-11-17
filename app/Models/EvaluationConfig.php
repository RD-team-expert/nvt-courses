<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'max_score',
        'applies_to',  // NEW

    ];

    protected $casts = [
        'applies_to' => 'string',  // NEW
        'max_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function types()
    {
        return $this->hasMany(EvaluationType::class);
    }

    /**
     * NEW: Scope for configs that apply to regular courses
     */
    public function scopeForRegular($query)
    {
        return $query->whereIn('applies_to', ['regular', 'both']);
    }

    /**
     * NEW: Scope for configs that apply to online courses
     */
    public function scopeForOnline($query)
    {
        return $query->whereIn('applies_to', ['online', 'both']);
    }

    /**
     * NEW: Scope for configs that apply to both types
     */
    public function scopeForBoth($query)
    {
        return $query->where('applies_to', 'both');
    }

    /**
     * NEW: Check if config applies to a specific course type
     */
    public function appliesTo(string $courseType): bool
    {
        return $this->applies_to === 'both' || $this->applies_to === $courseType;
    }
}
