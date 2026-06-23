<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ElementComponentHasGene extends Model {
    protected $table = 'element_component_has_genes';
    protected $fillable = ['element_component_id','gene_id','value'];
    protected $casts = ['element_component_id' => 'integer','gene_id' => 'integer','value' => 'integer'];
    public function elementComponent() { return $this->belongsTo(ElementComponent::class); }
    public function gene() { return $this->belongsTo(Gene::class); }
}
