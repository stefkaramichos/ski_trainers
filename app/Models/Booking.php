<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'mountain_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'selected_date',
        'selected_time',
        'people_count',
        'level',
        'notes',
        'status',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function mountain()
    {
        return $this->belongsTo(Mountain::class);
    }

 
}
