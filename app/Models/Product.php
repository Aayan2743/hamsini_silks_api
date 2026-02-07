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

    public function mainImage()
    {
        return $this->hasOne(\App\Models\ProductImage::class)
            ->where('is_main', true);
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

    public function videos()
    {
        return $this->hasMany(ProductVideo::class);
    }

    public function variantCombinations()
    {
        return $this->hasMany(
            ProductVariantCombination::class,
            'product_id'
        );
    }

    public function getImagesListAttribute()
    {
        return $this->images->map(fn($img) => [
            'id'        => $img->id,
            'is_main'   => (bool) $img->is_main,
            'image_url' => $img->image_url, // comes from ProductImage accessor
        ]);
    }
}
