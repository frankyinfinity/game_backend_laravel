<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementHasGene extends Model
{
    protected $table = 'element_has_genes';
    protected $fillable = ['element_id', 'gene_id', 'effect'];
}
