<?php
// app/Models/Mountain.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mountain extends Model
{
  

    protected $fillable = [
        'mountain_name',
        'slug',
        'latitude',
        'longitude',
        'description',
        'image_1',
        'image_2',
    ];

    public function users() {
        return $this->belongsToMany(User::class, 'mountain_user');
    }

    protected static function booted()
    {
        static::creating(function ($mountain) {
            if (empty($mountain->slug)) {
                $mountain->slug = Str::slug($mountain->mountain_name, '-');
            }
        });

        static::updating(function ($mountain) {
            if ($mountain->isDirty('mountain_name')) {
                $mountain->slug = Str::slug($mountain->mountain_name, '-');
            }
        });
    }

    // Tell Laravel to use slug instead of id in routes
    public function getRouteKeyName()
    {
        return 'slug';
    }

}
