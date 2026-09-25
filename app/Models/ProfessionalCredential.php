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

    public const PROFESSION_PSYCHOLOGIST = 'psychologist';
    public const PROFESSION_PSYCHIATRIST = 'psychiatrist';

    public const PROFESSIONS = [
        self::PROFESSION_PSYCHOLOGIST,
        self::PROFESSION_PSYCHIATRIST,
    ];

    public const COUNCIL_CRP = 'CRP';
    public const COUNCIL_CRM = 'CRM';

    public const COUNCILS = [
        self::COUNCIL_CRP,
        self::COUNCIL_CRM,
    ];

    public const COUNCIL_BY_PROFESSION = [
        self::PROFESSION_PSYCHOLOGIST => self::COUNCIL_CRP,
        self::PROFESSION_PSYCHIATRIST => self::COUNCIL_CRM,
    ];

    public const BADGE_UNVERIFIED = ['verified' => false, 'label' => 'Profissional'];

    protected $fillable = [
        'user_id',
        'profession',
        'council',
        'registration_number',
        'registration_region',
        'rqe_number',
        'epsi_registered',
        'status',
        'rejection_reason',
        'verification_method',
        'verification_source',
        'verified_by',
        'verified_at',
        'verified_snapshot',
        'next_review_at',
        'review_reminder_sent_at',
        'submitted_at',
    ];

    protected $casts = [
        'epsi_registered'   => 'boolean',
        'verified_at'       => 'datetime',
        'next_review_at'    => 'datetime',
        'review_reminder_sent_at' => 'datetime',
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

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** Aprovada e dentro de next_review_at + grace_days (null = nunca expira). */
    public function isActive(): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        if ($this->next_review_at === null) {
            return true;
        }

        $graceDays = (int) config('mindcat.credential.grace_days');

        return now()->lessThanOrEqualTo(
            $this->next_review_at->copy()->addDays($graceDays)
        );
    }

    /** Selo público exibido ao paciente. Nunca expõe número de registro. */
    public function publicBadge(): array
    {
        if (!$this->isActive()) {
            return self::BADGE_UNVERIFIED;
        }

        return match ($this->profession) {
            self::PROFESSION_PSYCHOLOGIST => ['verified' => true, 'label' => 'Psicólogo(a)'],
            self::PROFESSION_PSYCHIATRIST => filled($this->rqe_number)
                ? ['verified' => true, 'label' => 'Psiquiatra']
                : ['verified' => true, 'label' => 'Médico(a)'],
            default => self::BADGE_UNVERIFIED,
        };
    }
}