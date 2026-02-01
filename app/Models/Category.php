<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [

    ];

    /* Parent category */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /* Child categories (subcategories) */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }
}
