<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mountain extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'mountain_user');
    }    
}
