<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplexChimicalElement extends Model
{
    protected $fillable = ['name', 'symbol', 'image'];
    protected $appends = ['image_url'];

    public function details()
    {
        return $this->hasMany(ComplexChimicalElementDetail::class, 'parent_id');
    }

    public function getImageUrlAttribute()
    {
        $imagePath = 'storage/complex_chimical_elements/' . $this->id . '.png';
        if (file_exists(public_path($imagePath))) {
            return asset($imagePath . '?v=' . filemtime(public_path($imagePath)));
        }
        return null;
    }
}
