<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'brand_id',
        'description',
        'base_price',
        'discount',
        'status',
        'created_at',
        'updated_at',
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariantCombination::class);
    }

    public function seo()
    {
        return $this->hasOne(ProductSeoMeta::class);
    }

    public function taxAffinity()
    {
        return $this->hasOne(ProductTaxAffinity::class);
    }
}
