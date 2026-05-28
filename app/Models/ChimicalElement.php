<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChimicalElement extends Model
{
    protected $fillable = ['name', 'symbol', 'image'];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        $imagePath = 'storage/chimical_elements/' . $this->id . '.png';
        if (file_exists(public_path($imagePath))) {
            return asset($imagePath . '?v=' . filemtime(public_path($imagePath)));
        }
        return null;
    }
}
