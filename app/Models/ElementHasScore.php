<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasScore extends Model
{
    protected $table = 'element_has_scores';
    protected $fillable = ['element_id', 'score_id', 'amount'];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function score()
    {
        return $this->belongsTo(Score::class);
    }
}
