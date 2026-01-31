<?php
namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Validator;

class CouponController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => Coupon::orderBy('id', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'         => 'required|string|max:50|unique:coupons,code',
            'type'         => 'required|in:percent,flat',
            'value'        => 'required|numeric|min:0',
            'min_order'    => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'expiry_date'  => 'nullable|date',
            'is_active'    => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => Coupon::create($validator->validated()),
        ]);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code'         => 'required|string|max:50|unique:coupons,code,' . $id,
            'type'         => 'required|in:percent,flat',
            'value'        => 'required|numeric|min:0',
            'min_order'    => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'expiry_date'  => 'nullable|date',
            'is_active'    => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon->update($validator->validated());

        return response()->json([
            'success' => true,
            'data'    => $coupon,
        ]);
    }

    public function destroy($id)
    {
        Coupon::where('id', $id)->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
