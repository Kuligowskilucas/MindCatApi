<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProPatientLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['pro_id', 'patient_id', 'active'];

    public function professional()
    {
        return $this->belongsTo(User::class, 'pro_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
