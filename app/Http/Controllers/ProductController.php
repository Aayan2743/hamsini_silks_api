<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->search;
        $perPage = $request->perPage ?? 10;

        $products = Product::with(['category:id,name', 'brand:id,name'])
            ->when($search, fn($q) =>
                $q->where('name', 'like', "%{$search}%")
            )
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data'       => $products->getCollection()->map(fn($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'slug'           => $p->slug,
                'category_id'    => $p->category_id,
                'category_name'  => $p->category?->name,
                'brand_id'       => $p->brand_id,
                'brand_name'     => $p->brand?->name,
                'purchase_price' => $p->purchase_price,
                'base_price'     => $p->base_price,
                'discount'       => $p->discount,
                'status'         => $p->status,
            ]),
            'pagination' => [
                'currentPage' => $products->currentPage(),
                'totalPages'  => $products->lastPage(),
            ],
        ]);
    }

    /* ================= STORE ================= */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'category_id'    => 'nullable|exists:categories,id',
            'brand_id'       => 'nullable|exists:brands,id',
            'description'    => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'base_price'     => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'status'         => 'nullable|in:draft,active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        Product::create([
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
        ]);
    }

    /* ================= UPDATE ================= */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'category_id'    => 'nullable|exists:categories,id',
            'brand_id'       => 'nullable|exists:brands,id',
            'description'    => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'base_price'     => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'status'         => 'required|in:draft,active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
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
}
