<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiaryEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'content',];
    
    protected $casts = [
        'content' => 'encrypted',
    ];

    protected $hidden = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
