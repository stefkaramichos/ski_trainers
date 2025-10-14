<?php
// app/Models/BookingClaim.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingClaim extends Model
{
    protected $fillable = [
        'booking_id','instructor_id','token','claimed_at','invalidated_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    public function booking(){ return $this->belongsTo(Booking::class); }
    public function instructor(){ return $this->belongsTo(User::class, 'instructor_id'); }
}
