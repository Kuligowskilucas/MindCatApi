<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientInvite extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE  = 'active';
    public const STATUS_USED    = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'code',
        'patient_id',
        'expires_at',
        'used_at',
        'used_by_pro_id',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function usedByPro()
    {
        return $this->belongsTo(User::class, 'used_by_pro_id');
    }

    /** Pode ser usado agora? (ativo e não expirado) */
    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    /**
     * Código curto e legível, sem caracteres ambíguos (0/O, 1/I). A unicidade
     * real é garantida pelo índice unique + retry na criação (Fase 5d).
     */
    public static function generateCode(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $code;
    }
}