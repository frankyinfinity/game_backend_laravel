<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityTypeComponent extends Model
{
    protected $table = 'entity_type_components';

    protected $fillable = [
        'name',
        'symbol',
    ];

    /**
     * Get a list of curated FontAwesome 5 Free icons.
     */
    public static function getFontAwesomeIcons(): array
    {
        return config('font_awesome_icons', []);
    }
}
