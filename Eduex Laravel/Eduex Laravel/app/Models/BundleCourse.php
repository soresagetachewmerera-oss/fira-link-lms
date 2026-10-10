<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BundleCourse extends Pivot
{
    protected $table = 'bundle_courses';

    protected $fillable = [
        'bundle_id',
        'course_id',
    ];
}
