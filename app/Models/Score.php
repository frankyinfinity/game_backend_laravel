<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $fillable = ['name', 'image', 'group_score_id'];

    protected $casts = [
        'image' => 'string',
    ];

    public function groupScore()
    {
        return $this->belongsTo(GroupScore::class);
    }
}
