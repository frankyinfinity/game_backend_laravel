<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupScore extends Model
{
    protected $fillable = ['name'];

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}
