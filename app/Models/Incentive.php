<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incentive extends Model
{
    protected $fillable = [
        'user_level_id',        // NEW
        'user_level_tier_id',   // NEW
        'min_score',
        'max_score',
        'incentive_amount',
        'evaluation_config_id', // Optional foreign key for linking to evaluation_configs
    ];


 protected $casts = [
     'incentive_amount' => 'decimal:2', // Fixed: Only specify scale (decimal places)
     'min_score' => 'integer',
     'max_score' => 'integer',
 ];

    // Optional: Define the relationship if using evaluation_config_id
    public function evaluationConfig()
    {
        return $this->belongsTo(EvaluationConfig::class, 'evaluation_config_id');
    }
    // NEW: Get the user level
    public function userLevel()
    {
        return $this->belongsTo(UserLevel::class);
    }

    // NEW: Get the tier
    public function userLevelTier()
    {
        return $this->belongsTo(UserLevelTier::class);
    }
}
