<?php
// app/Models/Mountain.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mountain extends Model
{
    protected $fillable = ['mountain_name', 'latitude', 'longitude'];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    public function users() {
        return $this->belongsToMany(User::class, 'mountain_user');
    }
}
