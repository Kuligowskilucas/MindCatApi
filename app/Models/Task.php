<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['pro_id', 'patient_id', 'title', 'status', 'completed_at'];
    protected $casts = ['completed_at' => 'datetime'];

    public function professional()
    {
        return $this->belongsTo(User::class, 'pro_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
