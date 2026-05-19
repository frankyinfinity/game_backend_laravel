<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityBody extends Model
{
    const STATE_CREATED = 0;
    const STATE_FINISHED = 1;

    protected $fillable = [
        'name',
        'state',
        'image',
    ];

    public function isCreated()
    {
        return $this->state === self::STATE_CREATED;
    }

    public function isFinished()
    {
        return $this->state === self::STATE_FINISHED;
    }
}
