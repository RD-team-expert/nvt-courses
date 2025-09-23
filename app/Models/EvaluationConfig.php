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
    ];

    protected $casts = [
        'max_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function types()
    {
        return $this->hasMany(EvaluationType::class);
    }
}
