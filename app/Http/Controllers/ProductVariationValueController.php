<?php
namespace App\Http\Controllers;

use App\Models\ProductVariationValue;
use Illuminate\Http\Request;
use Validator;

class ProductVariationValueController extends Controller
{
    public function index()
    {
        return ProductVariationValue::with('variation')->get();
    }

    public function store(Request $request, $variationId)
    {
        $validator = Validator::make($request->all(), [
            'value'      => 'required|string|max:255',
            'color_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $value = ProductVariationValue::create([
            'variation_id' => $variationId,
            'value'        => $request->value,
            'color_code'   => $request->color_code,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $value,
        ]);
    }

    public function update(Request $request, $id)
    {
        $value = ProductVariationValue::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'value'      => 'required|string|max:255',
            'color_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $value->update([
            'value'      => $request->value,
            'color_code' => $request->color_code,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $value,
        ]);
    }

    public function destroy($id)
    {
        ProductVariationValue::where('id', $id)->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
