<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityInformation extends Model
{
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function genome(){
        return $this->belongsTo(Genome::class);
    }
    
}
