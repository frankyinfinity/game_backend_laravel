<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = ['name', 'docker_image_name', 'docker_tag', 'version', 'description', 'build_input_path', 'is_active'];

    public function containers()
    {
        return $this->hasMany(Container::class);
    }
}
