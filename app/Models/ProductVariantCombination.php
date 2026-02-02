<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantCombination extends Model
{
    protected $guarded = [

    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->belongsToMany(
            ProductVariationValue::class,
            'product_variant_combination_values',
            'variant_combination_id',
            'variation_value_id'
        );
    }

    public function images()
    {
        return $this->hasMany(ProductVariantImage::class, 'variant_combination_id');
    }
}
