<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    const PARENT_TYPE_ENTITY = 'Entity';
    const PARENT_TYPE_MAP = 'Map';
    const PARENT_TYPE_OBJECTIVE = 'Objective';

}
