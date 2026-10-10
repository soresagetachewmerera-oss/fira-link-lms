<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Added this line

class FocusSession extends Model
{
    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'duration',
        'type',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
