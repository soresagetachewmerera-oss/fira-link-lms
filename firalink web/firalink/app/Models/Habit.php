<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\HabitLog;

class Habit extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'target_days_per_week',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(HabitLog::class);
    }
}
