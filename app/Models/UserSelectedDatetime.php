<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSelectedDatetime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'selected_date',
        'selected_time',
    ];
}
