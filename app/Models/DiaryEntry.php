<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\EncryptedDiaryContent;



class DiaryEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'content',];
    
    protected $casts = [
        'content'            => EncryptedDiaryContent::class,
        'encryption_version' => 'integer',
    ];

    protected $hidden = ['encryption_version'];

    protected static function booted(): void
    {
        static::saving(function (self $entry): void {
            if ($entry->isDirty('content')) {
                $entry->encryption_version = 1;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
