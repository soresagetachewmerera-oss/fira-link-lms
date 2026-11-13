<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CoffeeReadinessAssessment extends Model
{
    protected $table = 'coffee_readiness_assessments';

    protected $fillable = [
        'user_id',
        'overall_score',
        'readiness_tier',
        'category_scores_json',
        'answers_json',
        'action_plan_json',
    ];

    protected $casts = [
        'overall_score' => 'float',
        'category_scores_json' => 'array',
        'answers_json' => 'array',
        'action_plan_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
