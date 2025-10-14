<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UserSelectedDatetime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'selected_date',
        'selected_time',
        'is_reserved',   // <-- add
    ];

    protected $casts = [
        'selected_date' => 'date:Y-m-d',
        'is_reserved'   => 'boolean',
    ];

    // Handy scope to fetch one slot
    public function scopeSlot($q, int $userId, string $dateYmd, string $timeHi)
    {
        return $q->where('user_id', $userId)
                 ->where('selected_date', $dateYmd)
                 ->where('selected_time', $timeHi.':00'); // DB is TIME
    }
}
 