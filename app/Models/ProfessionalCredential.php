<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfessionalCredential extends Model
{
    use HasFactory, SoftDeletes;

    // ── Estados da máquina de verificação (ver doc de design da Fase 5) ──
    public const STATUS_PENDING      = 'pending';
    public const STATUS_SUBMITTED    = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED     = 'approved';
    public const STATUS_REJECTED     = 'rejected';
    public const STATUS_SUSPENDED    = 'suspended';
    public const STATUS_EXPIRED      = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_SUSPENDED,
        self::STATUS_EXPIRED,
    ];

    // ── Método da verificação (auditoria) ──
    public const METHOD_MANUAL       = 'manual';
    public const METHOD_OCR_ASSISTED = 'ocr_assisted';
    public const METHOD_API          = 'api';

    protected $fillable = [
        'user_id',
        'crp_number',
        'crp_region',
        'epsi_registered',
        'status',
        'rejection_reason',
        'verification_method',
        'verification_source',
        'verified_by',
        'verified_at',
        'verified_snapshot',
        'next_review_at',
        'submitted_at',
    ];

    protected $casts = [
        'epsi_registered'   => 'boolean',
        'verified_at'       => 'datetime',
        'next_review_at'    => 'datetime',
        'submitted_at'      => 'datetime',
        'verified_snapshot' => 'array',
    ];

    /** O profissional dono da credencial. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** O admin que decidiu (nullable). */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Documentos comprobatórios (carteira, e-Psi, diploma). */
    public function documents()
    {
        return $this->hasMany(CredentialDocument::class, 'credential_id');
    }

    /** Único estado que libera os poderes clínicos de `pro`. */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}