<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $guarded = [

    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
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
