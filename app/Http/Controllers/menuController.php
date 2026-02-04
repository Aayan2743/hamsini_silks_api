<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class menuController extends Controller
{

    public function menu()
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', 1)
            ->with(['children' => function ($q) {
                $q->where('is_active', 1)->orderBy('name');
            }])
            ->get();

        $menu = $categories->map(function ($cat) {
            return [
                'key'   => Str::slug($cat->name),
                'label' => $cat->name,
                'items' => $cat->children->pluck('name')->values(),
            ];
        })->values();

        return response()->json($menu);
    }

    public function products_working(Request $request)
    {
        $query = Product::where('status', 'active')
            ->with('category:id,name,slug,parent_id');

        if ($request->filled('category')) {
            $category = Category::where('slug', $request->category)->first();

            if ($category) {
                if ($category->parent_id) {
                    // sub category
                    $query->where('category_id', $category->id);
                } else {
                    // main category
                    $subIds = Category::where('parent_id', $category->id)
                        ->pluck('id');

                    $query->whereIn('category_id', $subIds);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate(12),
        ]);
    }

    public function productsdddd(Request $request)
    {
        $query = Product::where('status', 'active')
            ->with([
                'category:id,name,slug,parent_id',

                // 🔥 MEDIA
                'images:id,product_id,image_path,is_primary',
                'videos:id,product_id,video_url',

                // 🔥 VARIANTS
                'variantCombinations.values.variation:id,name',
            ]);

        /* ================= CATEGORY FILTER ================= */
        if ($request->filled('category')) {
            $category = Category::where('slug', $request->category)->first();

            if ($category) {
                if ($category->parent_id) {
                    $query->where('category_id', $category->id);
                } else {
                    $subIds = Category::where('parent_id', $category->id)->pluck('id');
                    $query->whereIn('category_id', $subIds);
                }
            }
        }

        /* ================= COLOR + PRICE (SAME VARIANT) ================= */
        if (
            $request->filled('colors') ||
            $request->filled('min_price') ||
            $request->filled('max_price')
        ) {
            $colorIds = $request->filled('colors')
                ? explode(',', $request->colors)
                : [];

            $min = $request->min_price ?? 0;
            $max = $request->max_price ?? PHP_INT_MAX;

            $query->whereHas('variantCombinations', function ($q) use ($colorIds, $min, $max) {

                $q->whereBetween('extra_price', [$min, $max]);

                if (! empty($colorIds)) {
                    $q->whereHas('values', function ($qq) use ($colorIds) {
                        $qq->whereIn(
                            'product_variant_combination_values.variation_value_id',
                            $colorIds
                        );
                    });
                }
            });
        }

        $products = $query->paginate(12);

        /* ================= TRANSFORM RESPONSE ================= */
        $products->getCollection()->transform(function ($product) {

            /* -------- COLORS -------- */
            $colors = $product->variantCombinations
                ->flatMap(fn($vc) => $vc->values)
                ->filter(fn($v) => $v->variation?->name === 'Color')
                ->unique('id')
                ->values()
                ->map(fn($v) => [
                    'id'   => $v->id,
                    'name' => $v->value,
                    'code' => $v->color_code,
                ]);

            /* -------- AMOUNT -------- */
            $prices = $product->variantCombinations
                ->pluck('extra_price')
                ->filter();

            $minPrice = $prices->min();
            $maxPrice = $prices->max();

            /* -------- IMAGES -------- */
            $images = $product->images->map(fn($img) => [
                'id'         => $img->id,
                'url'        => asset('storage/' . $img->image_path),
                'is_primary' => (bool) $img->is_primary,
            ]);

            $primaryImage = optional(
                $product->images->firstWhere('is_primary', 1)
            )->image_path
                ? asset('storage/' . $product->images->firstWhere('is_primary', 1)->image_path)
                : ($images->first()['url'] ?? null);

            /* -------- VIDEOS -------- */
            $videos = $product->videos->map(fn($v) => [
                'id'  => $v->id,
                'url' => $v->video_url,
            ]);

            return [
                'id'          => $product->id,
                'name'        => $product->name,
                'slug'        => $product->slug,
                'description' => $product->description,

                'category'    => [
                    'id'   => $product->category?->id,
                    'name' => $product->category?->name,
                    'slug' => $product->category?->slug,
                ],

                // 🔥 PRICE
                'price'       => [
                    'min' => $minPrice,
                    'max' => $maxPrice,
                ],

                                                // 🔥 MEDIA
                'image'       => $primaryImage, // for cards
                'images'      => $images,       // for gallery
                'videos'      => $videos,

                // 🔥 COLORS
                'colors'      => $colors,

                'status'      => $product->status,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::where('status', 'active')
            ->with([
                'category:id,name,slug,parent_id',
                'images:id,product_id,image_path,is_primary',
                'videos:id,product_id,video_url',
                'variantCombinations.values.variation:id,name',
            ]);

        /* ================= CATEGORY FILTER ================= */
        if ($request->filled('category')) {
            $category = Category::where('slug', $request->category)->first();

            if ($category) {
                if ($category->parent_id) {
                    $query->where('category_id', $category->id);
                } else {
                    $subIds = Category::where('parent_id', $category->id)->pluck('id');
                    $query->whereIn('category_id', $subIds);
                }
            }
        }

        /* ================= SLUG FILTER ================= */
        if ($request->filled('slug')) {
            $query->where('slug', $request->slug);
        }

        /* ================= COLOR + PRICE (SAME VARIANT) ================= */
        if (
            $request->filled('colors') ||
            $request->filled('min_price') ||
            $request->filled('max_price')
        ) {
            $colorIds = $request->filled('colors')
                ? explode(',', $request->colors)
                : [];

            $min = $request->min_price ?? 0;
            $max = $request->max_price ?? PHP_INT_MAX;

            $query->whereHas('variantCombinations', function ($q) use ($colorIds, $min, $max) {
                $q->whereBetween('extra_price', [$min, $max]);

                if (! empty($colorIds)) {
                    $q->whereHas('values', function ($qq) use ($colorIds) {
                        $qq->whereIn(
                            'product_variant_combination_values.variation_value_id',
                            $colorIds
                        );
                    });
                }
            });
        }

        $products = $query->paginate(12);

        // (transform code stays EXACTLY same as before)

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

}
