<?php
namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentGatewayController extends Controller
{
    public function show()
    {
        $data = PaymentGateway::first();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * CREATE or UPDATE (single row)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_key'        => 'nullable|string',
            'razorpay_secret'     => 'nullable|string',
            'razorpay_enabled'    => 'nullable|boolean',

            'cashfree_app_id'     => 'nullable|string',
            'cashfree_secret'     => 'nullable|string',
            'cashfree_enabled'    => 'nullable|boolean',

            'phonepe_merchant_id' => 'nullable|string',
            'phonepe_secret'      => 'nullable|string',
            'phonepe_enabled'     => 'nullable|boolean',

            'cod_enabled'         => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $now = Carbon::now();

        $gateway = PaymentGateway::updateOrCreate(
            ['id' => 1], // 👈 enforce SINGLE ROW
            array_merge(
                $validator->validated(),
                ['created_at' => $now, 'updated_at' => $now]
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment gateway settings saved successfully',
            'data'    => $gateway,
        ]);
    }

    /**
     * DELETE settings (optional)
     */
    public function destroy()
    {
        PaymentGateway::where('id', 1)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment gateway settings deleted',
        ]);
    }
}
