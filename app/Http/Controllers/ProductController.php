<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $search  = $request->search;
        $perPage = $request->perPage ?? 10;

        // $products = Product::with([
        //     'category:id,name',
        //     'brand:id,name',
        //     'category.parent:id,name',
        // ])
        //     ->withMin('variantCombinations as min_price', 'extra_price')
        //     ->withMin('variantCombinations as min_discount', 'discount')
        //     ->when($search, fn($q) =>
        //         $q->where('name', 'like', "%{$search}%")
        //     )
        //     ->orderBy('id', 'desc')
        //     ->paginate($perPage);

        $products = Product::with([
            'category:id,name,parent_id',
            'category.parent:id,name',
            'brand:id,name',
        ])
        ->withMin('variantCombinations as min_price', 'extra_price')
        ->withMin('variantCombinations as min_discount', 'discount')
        ->orderBy('id', 'desc')
        ->paginate($perPage);

        return response()->json([
            'data'       => $products->getCollection()->map(fn($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'slug'          => $p->slug,
                'category_id'   => $p->category_id,
                'category_name' => $p->category?->name,
                'category_main' => $p->category?->parent?->name,
                'brand_id'      => $p->brand_id,
                'brand_name'    => $p->brand?->name,

                // 🔥 VARIATION PRICES
                'price'         => $p->min_price,
                'discount'      => $p->min_discount,
                'final_price'   => max(
                    0,
                    ($p->min_price ?? 0) - ($p->min_discount ?? 0)
                ),

                'status'        => $p->status,
            ]),
            'pagination' => [
                'currentPage' => $products->currentPage(),
                'totalPages'  => $products->lastPage(),
            ],
        ]);
    }

    public function fetchById($id)
    {
        $product = Product::with([
            // 'category',
            // 'brand:id,name',
            // 'images:id,product_id,image_path,is_primary',
            // 'variantCombinations:id,product_id,sku,purchase_price,extra_price,discount,quantity,low_quantity',
            // 'seo',
            // 'taxAffinity',
            // 'videos',
            // 'variantCombinations.values.value', // 🔥 REQUIRED
            // 'variantCombinations.images',       // 🔥 REQUIRED

            'category:id,name,parent_id',
            'category.parent:id,name',
            'brand:id,name',
            'images:id,product_id,image_path,is_primary',
            'videos:id,product_id,video_url',

            'variantCombinations:id,product_id,sku,purchase_price,extra_price,discount,quantity,low_quantity',

            // 🔥 REQUIRED FOR EDIT FLOW

            'variantCombinations.values:id,value,variation_id',
            'variantCombinations.images:id,variant_combination_id,image_path',

            'seo',
            'taxAffinity',
        ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $product->id,
                'name'                => $product->name,

                'category_id'         => $product->category_id,
                'category'            => $product->category
                    ? [
                    'id'        => $product->category->id,
                    'name'      => $product->category->name,
                    'parent_id' => $product->category->parent_id,
                    'main_name' => $product->category->parent->name,
                ]
                    : null,

                'brand_id'            => $product->brand_id,
                'brand'               => $product->brand
                    ? [
                    'id'   => $product->brand->id,
                    'name' => $product->brand->name,
                ]
                    : null,

                'description'         => $product->description,
                'base_price'          => $product->base_price,
                'discount'            => $product->discount,
                'status'              => $product->status,
                'video'               => $product->videos->map(fn($v) => [
                    'id'        => $v->id,
                    'video_url' => $v->video_url,
                ]),

                /* Gallery */
                'gallery'             => $product->images->map(fn($img) => [
                    'id'         => $img->id,
                    'url'        => asset('storage/' . $img->image_path),
                    'is_primary' => $img->is_primary,
                ]),

                /* Variations */
                // 'variantCombinations' => $product->variantCombinations->map(fn($v) => [
                //     'id'             => $v->id,
                //     'sku'            => $v->sku,
                //     'purchase_price' => $v->purchase_price,
                //     'extra_price'    => $v->extra_price,
                //     'discount'       => $v->discount,
                //     'quantity'       => $v->quantity,
                //     'low_quantity'   => $v->low_quantity,
                // ]),

                'variantCombinations' => $product->variantCombinations->map(function ($combo) {
                    return [
                        'id'                 => $combo->id,
                        'sku'                => $combo->sku,
                        'purchase_price'     => $combo->purchase_price,
                        'extra_price'        => $combo->extra_price,
                        'discount'           => $combo->discount,
                        'quantity'           => $combo->quantity,
                        'low_quantity'       => $combo->low_quantity,

                        // 🔥 THIS FIXES EDIT PREFILL
                        'combination_values' => $combo->values->map(fn($v) => [
                            'value' => [
                                'id'           => $v->id,
                                'value'        => $v->value,
                                'variation_id' => $v->variation_id,
                            ],
                        ]),

                        'images'             => $combo->images->map(fn($img) => [
                            'id'        => $img->id,
                            'image_url' => asset('storage/' . $img->image_path),
                        ]),
                    ];
                }),

                'meta'                => $product->seo ?? (object) [],
                'product_tax'         => $product->taxAffinity,
            ],
        ]);

    }

    /* ================= STORE ================= */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'category_id'    => 'required|exists:categories,id',
            'brand_id'       => 'nullable|exists:brands,id',
            'description'    => 'required|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'base_price'     => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'status'         => 'nullable|in:draft,active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $product = Product::create([
            'name'           => $request->name,
            'slug'           => Str::slug($request->name),
            'category_id'    => $request->category_id,
            'brand_id'       => $request->brand_id,
            'description'    => $request->description,
            'purchase_price' => $request->purchase_price,
            'base_price'     => $request->base_price,
            'discount'       => $request->discount,
            'status'         => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => [
                'id' => $product->id,
            ],
        ]);
    }

    /* ================= UPDATE ================= */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'category_id'    => 'required|exists:categories,id',
            'brand_id'       => 'nullable|exists:brands,id',
            'description'    => 'required|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'base_price'     => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'status'         => 'nullable|in:draft,active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $product->update([
            'name'           => $request->name,
            'slug'           => Str::slug($request->name),
            'category_id'    => $request->category_id,
            'brand_id'       => $request->brand_id,
            'description'    => $request->description,
            'purchase_price' => $request->purchase_price,
            'base_price'     => $request->base_price,
            'discount'       => $request->discount,
            'status'         => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => [
                'id' => $product->id,
            ],
        ]);
    }

    /* ================= DELETE ================= */
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    public function publish(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // ⛔ Prevent double publish
        if ($product->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Product is already published',
            ], 409);
        }

        if (! $product->variants()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Add product variants before publishing',
            ], 422);
        }

        // ✅ Publish
        $product->update([
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product published successfully',
            'data'    => [
                'id'     => $product->id,
                'status' => $product->status,
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid file',
                'errors'  => $validator->errors()->all(),
            ], 422);
        }

        $rows = Excel::toArray([], $request->file('file'))[0];

        $header = array_map('strtolower', $rows[0]);
        unset($rows[0]);

        $inserted = 0;
        $errors   = [];

        foreach ($rows as $index => $row) {
            $data = array_combine($header, $row);

            $rowValidator = Validator::make($data, [
                'name'        => 'required|string|max:255',
                'category_id' => 'required|integer',
                'brand_id'    => 'nullable|integer',

            ]);

            if ($rowValidator->fails()) {
                $errors[] = [
                    'row'    => $index + 2,
                    'errors' => $rowValidator->errors(),
                ];
                continue;
            }

            Product::create([
                'name'        => $data['name'],
                'slug'        => Str::slug($data['name']), // ✅ FIX
                'category_id' => $data['category_id'],
                'brand_id'    => $data['brand_id'] ?? null,
                'description' => $data['description'] ?? '',

            ]);

            $inserted++;
        }

        return response()->json([
            'success'  => true,
            'inserted' => $inserted,
            'errors'   => $errors,
        ]);
    }

}