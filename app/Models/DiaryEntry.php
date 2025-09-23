<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaryEntry extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'content',];

    protected $hidden = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
