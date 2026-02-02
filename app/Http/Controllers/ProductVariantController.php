<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariantCombination;
use App\Models\ProductVariantCombinationValue;
use App\Models\ProductVariantImage;
use App\Services\WebpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{
    public function storessss(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'variants'                       => 'required|array|min:1',

            'variants.*.variation_value_ids' => 'required|array|min:1',
            'variants.*.sku'                 => 'nullable|string|max:100',
            'variants.*.purchase_price'      => 'nullable|numeric|min:0', // ✅
            'variants.*.extra_price'         => 'nullable|numeric|min:0',
            'variants.*.quantity'            => 'nullable|integer|min:0',
            'variants.*.low_quantity'        => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $createdVariants = [];

        foreach ($request->variants as $variant) {

            // 1️⃣ Create variant combination
            $combo = ProductVariantCombination::create([
                'product_id'     => $product->id,
                'sku'            => $variant['sku'] ?? null,
                'purchase_price' => $variant['purchase_price'] ?? 0,
                'extra_price'    => $variant['extra_price'] ?? 0,
                'quantity'       => $variant['quantity'] ?? 0,
                'low_quantity'   => $variant['low_quantity'] ?? 0,
            ]);

            // 2️⃣ Store variation values
            foreach ($variant['variation_value_ids'] as $valueId) {
                ProductVariantCombinationValue::create([
                    'variant_combination_id' => $combo->id,
                    'variation_value_id'     => $valueId,
                ]);
            }

            $createdVariants[] = $combo;
        }

        return response()->json([
            'success' => true,
            'data'    => $createdVariants,
        ], 201);
    }

    public function store(Request $request, Product $product)
    {
        // ✅ FIX: Decode variants JSON when sent via FormData
        if ($request->has('variants') && is_string($request->variants)) {
            $request->merge([
                'variants' => json_decode($request->variants, true),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'variants'                       => 'required|array|min:1',
            'variants.*.variation_value_ids' => 'required|array|min:1',
            'variants.*.sku'                 => 'nullable|string|max:100',
            'variants.*.purchase_price'      => 'nullable|numeric|min:0',
            'variants.*.extra_price'         => 'nullable|numeric|min:0',
            'variants.*.quantity'            => 'nullable|integer|min:0',
            'variants.*.low_quantity'        => 'nullable|integer|min:0',

            // 🔥 images validation
            'variant_images'                 => 'nullable|array',
            'variant_images.*'               => 'nullable|array',
            'variant_images.*.*'             => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $createdVariants = [];

            foreach ($request->variants as $index => $variant) {

                $combo = ProductVariantCombination::create([
                    'product_id'     => $product->id,
                    'sku'            => $variant['sku'] ?? null,
                    'purchase_price' => $variant['purchase_price'] ?? 0,
                    'extra_price'    => $variant['extra_price'] ?? 0,
                    'quantity'       => $variant['quantity'] ?? 0,
                    'low_quantity'   => $variant['low_quantity'] ?? 0,
                ]);

                foreach ($variant['variation_value_ids'] as $valueId) {
                    ProductVariantCombinationValue::create([
                        'variant_combination_id' => $combo->id,
                        'variation_value_id'     => $valueId,
                    ]);
                }

                // ✅ Variant Images
                if ($request->hasFile("variant_images.$index")) {
                    foreach ($request->file("variant_images.$index") as $file) {

                        $temp = $file->store('temp', 'public');
                        $src  = storage_path('app/public/' . $temp);

                        $filename     = Str::uuid() . '.webp';
                        $relativePath = "products/variant-images/$filename";
                        $dest         = storage_path('app/public/' . $relativePath);

                        WebpService::convert($src, $dest, 70);
                        Storage::disk('public')->delete($temp);

                        ProductVariantImage::create([
                            'variant_combination_id' => $combo->id,
                            'image_path'             => $relativePath,
                        ]);
                    }
                }

                $createdVariants[] = $combo;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $createdVariants,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

}
