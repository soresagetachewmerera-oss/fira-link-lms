<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class CommunityAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'content',
        'is_accepted',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CommunityQuestion::class, 'question_id');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(CommunityVote::class, 'votable');
    }
}
