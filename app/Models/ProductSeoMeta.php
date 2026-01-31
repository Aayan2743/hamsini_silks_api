<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSeoMeta extends Model
{
    protected $table = 'product_seo_meta';

    protected $fillable = [
        'product_id',
        'meta_title',
        'meta_description',
        'meta_tags',
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
