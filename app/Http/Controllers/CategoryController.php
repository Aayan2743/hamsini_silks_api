<?php
namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image'     => 'nullable|image|max:2048',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->storeAs('categories', $imageName, 'public');
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

        $request->validate([
            'name'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $id,
            'image'     => 'nullable|image|max:2048',
        ]);

        $imageName = $category->image;

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete('categories/' . $category->image);
            }

            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->storeAs('categories', $imageName, 'public');
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