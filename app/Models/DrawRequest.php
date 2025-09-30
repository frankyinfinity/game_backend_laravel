<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrawRequest extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $casts = [
        'items' => 'json',
    ];

    public function player(){
        return $this->belongsTo(Player::class);
    }

}
