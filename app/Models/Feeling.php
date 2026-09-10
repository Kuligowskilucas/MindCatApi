<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feeling extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'sort_order',
    ];

    public function moods()
    {
        return $this->belongsToMany(UserMoodTracking::class, 'mood_feeling', 'feeling_id', 'mood_id');
    }
}
