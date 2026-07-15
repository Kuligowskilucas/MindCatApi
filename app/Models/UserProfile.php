<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'use_ai',
        'treatment_type',
        'tdah_reminder',
        'push_notifications',
        'progress_bar',
        'consent_share_with_professional',
    ];

    protected $casts = [
        'use_ai'                          => 'boolean',
        'tdah_reminder'                   => 'boolean',
        'push_notifications'              => 'boolean',
        'progress_bar'                    => 'boolean',
        'consent_share_with_professional' => 'boolean',
    ];

    protected $hidden = ['diary_password_hash'];

    protected $appends = ['has_diary_password'];

    /**
     * Indica se o paciente já definiu senha do diário, sem vazar o hash.
     * Lê direto de $this->attributes (e não de $this->diary_password_hash)
     * porque o valor está em $hidden — o acesso ao array bruto continua válido,
     * e empty() sobre índice inexistente não emite warning.
     */
    public function getHasDiaryPasswordAttribute(): bool
    {
        return !empty($this->attributes['diary_password_hash']);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}