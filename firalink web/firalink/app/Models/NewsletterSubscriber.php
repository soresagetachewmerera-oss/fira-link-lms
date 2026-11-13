<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Newsletter Subscriber Model
 *
 * This model handles the newsletter subscriber functionality.
 *
 * @package App\Models
 */
class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'source',
    ];
}

