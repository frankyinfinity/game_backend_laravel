<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityComponent extends Model
{
    // State Constants
    const STATE_CREATED = 0;
    const STATE_FINISHED = 1;

    protected $fillable = [
        'name',
        'image',
        'state',
    ];

    /**
     * Check if the component is in created state.
     */
    public function isCreated(): bool
    {
        return $this->state === self::STATE_CREATED;
    }

    /**
     * Check if the component is in finished state.
     */
    public function isFinished(): bool
    {
        return $this->state === self::STATE_FINISHED;
    }

    /**
     * Get human-readable labels for the states.
     */
    public static function getStateLabels(): array
    {
        return [
            self::STATE_CREATED => 'Creato',
            self::STATE_FINISHED => 'Completato',
        ];
    }
}
