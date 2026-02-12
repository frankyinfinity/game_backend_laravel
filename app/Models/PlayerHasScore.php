<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Player;
use App\Models\Score;

class PlayerHasScore extends Model
{
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function player(){
        return $this->belongsTo(Player::class);
    }

    public function score(){
        return $this->belongsTo(Score::class);
    }

}
