<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_config_id',
        'type_name',
        'score_value',
    ];

    protected $casts = [
        'score_value' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function config()
    {
        return $this->belongsTo(EvaluationConfig::class);
    }
}
