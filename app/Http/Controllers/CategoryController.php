<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\WebpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->search;
        $perPage = $request->perPage ?? 5;

        $query = Category::query()
            ->with('parent')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc');

        $categories = $query->paginate($perPage);

        return response()->json([
            'data'       => $categories->getCollection()->map(function ($cat) {
                return [
                    'id'             => $cat->id,
                    'name'           => $cat->name,
                    'parent_id'      => $cat->parent_id,
                    'parent_name'    => $cat->parent?->name,
                    'full_image_url' => $cat->image
                        ? asset('storage/categories/' . $cat->image)
                        : null,
                ];
            }),
            'pagination' => [
                'totalPages'  => $categories->lastPage(),
                'currentPage' => $categories->currentPage(),
            ],
        ]);
    }

    /* ================= CREATE CATEGORY ================= */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image'     => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $imageName = null;

        if ($request->hasFile('image')) {

            $file     = $request->file('image');
            $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // ✅ save only webp
            $imageName = time() . '_' . Str::slug($baseName) . '.webp';

            $src  = $file->getPathname();
            $dest = storage_path('app/public/categories/' . $imageName);

            // 🔥 convert + resize
            WebpService::convert(
                $src,
                $dest,
                60, // quality

            );
        }

        Category::create([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'parent_id' => $request->parent_id,
            'image'     => $imageName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
        ]);
    }

    /* ================= UPDATE CATEGORY ================= */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $id,
            'image'     => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $imageName = $category->image;

        if ($request->hasFile('image')) {

            // 🗑️ delete old image
            if ($category->image) {
                Storage::disk('public')->delete('categories/' . $category->image);
            }

            $file     = $request->file('image');
            $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // ✅ always save as webp
            $imageName = time() . '_' . Str::slug($baseName) . '.webp';

            $src  = $file->getPathname();
            $dest = storage_path('app/public/categories/' . $imageName);

            // 🔥 convert + resize
            WebpService::convert(
                $src,
                $dest,
                60, // quality

            );
        }

        $category->update([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'parent_id' => $request->parent_id,
            'image'     => $imageName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
        ]);
    }

    /* ================= DELETE CATEGORY ================= */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Optional safety: prevent deleting parent with children
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Delete subcategories first',
            ], 422);
        }

        if ($category->image) {
            Storage::disk('public')->delete('categories/' . $category->image);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

}
