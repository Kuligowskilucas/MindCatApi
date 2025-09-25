<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserActivity extends Model
{

    use HasFactory, SoftDeletes;
    
    protected $table = 'user_activities';

    // public $timestamps = true; // padrão. Se sua tabela não tiver, mude para false.

    protected $fillable = [
        'user_id',
        'activity_name',  // ex.: 'breathing'
        'is_completed',   // tinyint/bool
        'completed_at',   // datetime
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /* RELATIONSHIPS */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
