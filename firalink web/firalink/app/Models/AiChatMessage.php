<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AiChatMessage extends Model
{
    protected $fillable = ['user_id', 'message', 'is_ai'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
