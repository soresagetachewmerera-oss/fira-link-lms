<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class CommunityQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'tags',
        'views',
    ];

    protected $casts = [
        'tags' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CommunityAnswer::class, 'question_id');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(CommunityVote::class, 'votable');
    }
}
