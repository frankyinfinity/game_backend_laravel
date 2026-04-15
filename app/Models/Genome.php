<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Genome extends Model
{
     
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function entity(){
        return $this->belongsTo(Entity::class);
    }

    public function gene(){
        return $this->belongsTo(Gene::class);
    }

    public function entityInformations(){
        return $this->hasMany(EntityInformation::class);
    }

    protected static function booted()
    {
        static::updating(function ($genome) {
            if ($genome->isDirty('modifier')) {
                $oldModifier = $genome->getOriginal('modifier') ?? 0;
                $newModifier = $genome->modifier ?? 0;
                Log::info('[Genome modifier changed]', [
                    'genome_id' => $genome->id,
                    'entity_id' => $genome->entity_id,
                    'old_modifier' => $oldModifier,
                    'new_modifier' => $newModifier,
                ]);
            }
        });
    }

}
