<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CredentialDocument extends Model
{
    use HasFactory;

    public const KIND_CRP_CARD   = 'crp_card';
    public const KIND_EPSI_PROOF = 'epsi_proof';
    public const KIND_DIPLOMA    = 'diploma';
    public const KIND_OTHER      = 'other';

    protected $fillable = [
        'credential_id',
        'kind',
        'storage_path',
        'original_name',
        'mime',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function credential()
    {
        return $this->belongsTo(ProfessionalCredential::class, 'credential_id');
    }
}