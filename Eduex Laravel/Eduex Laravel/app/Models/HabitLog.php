<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HabitLog extends Model
{
    protected $fillable = [
        'habit_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'date',
    ];

    public function habit()
    {
        return $this->belongsTo(Habit::class);
    }
}
