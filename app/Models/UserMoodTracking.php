<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserMoodTracking extends Model
{

    use HasFactory, SoftDeletes;
    // sua tabela não segue o plural padrão; informe explicitamente
    protected $table = 'user_mood_tracking';

    // se a tabela tem PK "id" autoincrement padrão, não precisa alterar
    // protected $primaryKey = 'id';

    // se a tabela tem created_at/updated_at, mantenha true (padrão).
    // Se NÃO tiver, descomente a linha abaixo:
    // public $timestamps = false;

    protected $fillable = [
        'user_id',
        'mood_level',        // int 1..5
        'mood_description',  // string/null
        'recorded_at',       // datetime
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    /* RELATIONSHIPS */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
