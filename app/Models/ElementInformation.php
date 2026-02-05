<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementInformation extends Model
{
    protected $table = 'element_informations';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'element_id',
        'gene_id',
        'min_value',
        'max_value',
        'max_from',
        'max_to',
        'value'
    ];

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function gene()
    {
        return $this->belongsTo(Gene::class);
    }
}
