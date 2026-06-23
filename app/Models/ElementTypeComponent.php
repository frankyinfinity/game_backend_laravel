<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementTypeComponent extends Model
{
    protected $fillable = ['name', 'symbol'];

    public static function getFontAwesomeIcons(): array
    {
        return config('font_awesome_icons', []);
    }
}
