<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'Setting';

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Setting keys (all in English)
    |--------------------------------------------------------------------------
    */

    /**
     * Score required to perform an evolution.
     */
    public const KEY_EVOLUTION_SCORE = 'evolution_score';

    /**
     * Cost paid (in the evolution score) for each evolution step.
     */
    public const KEY_EVOLUTION_STEP_COST = 'evolution_step_cost';

    /**
     * Human readable labels for known keys.
     */
    public const LABELS = [
        self::KEY_EVOLUTION_SCORE => 'Punteggio per Evoluzione',
        self::KEY_EVOLUTION_STEP_COST => 'Costo per Step di Evoluzione',
    ];

    /**
     * Default values seeded for known keys.
     */
    public const DEFAULTS = [
        self::KEY_EVOLUTION_SCORE => '0',
        self::KEY_EVOLUTION_STEP_COST => '10',
    ];

    /**
     * Keys rendered as a select of the Score records (the score id is stored).
     */
    public const SCORE_SELECT_KEYS = [
        self::KEY_EVOLUTION_SCORE,
    ];

    /**
     * Keys rendered as numeric inputs.
     */
    public const NUMERIC_KEYS = [
        self::KEY_EVOLUTION_STEP_COST,
    ];

    /**
     * Explanations shown next to each field in the settings form.
     */
    public const DESCRIPTIONS = [
        self::KEY_EVOLUTION_SCORE => 'Lo Score usato per l\'evoluzione; viene salvato il suo ID.',
        self::KEY_EVOLUTION_STEP_COST => 'Quanto costa (nel punteggio scelto) ogni step di evoluzione.',
    ];

    /**
     * Get a setting value by key, with an optional default.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::query()->where('key', $key)->first();

        if ($setting) {
            return $setting->value;
        }

        return $default ?? (static::DEFAULTS[$key] ?? null);
    }

    /**
     * Set a setting value by key (creates the row if missing).
     */
    public static function setValue(string $key, ?string $value): static
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Display label for a key (falls back to the raw key).
     */
    public function getLabelAttribute(): string
    {
        return static::LABELS[$this->key] ?? $this->key;
    }

    /**
     * Explanation shown under the field (empty when unknown).
     */
    public function getDescriptionAttribute(): string
    {
        return static::DESCRIPTIONS[$this->key] ?? '';
    }
}
