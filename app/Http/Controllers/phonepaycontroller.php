<?php
namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PhonePeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PhonePayController extends Controller
{
    protected PhonePeService $phonePe;

    public function __construct(PhonePeService $phonePe)
    {
        $this->phonePe = $phonePe;
    }

    /**
     * Create Payment
     */
    public function create(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $merchantOrderId = 'ORDER_' . Str::uuid();

        // Payment::create([
        //     'merchant_order_id' => $merchantOrderId,
        //     'amount'            => $request->amount,
        //     'status'            => 'PENDING',
        // ]);

        $response = $this->phonePe->createPayment(
            amount: $request->amount,
            merchantOrderId: $merchantOrderId,
            redirectUrl: config('app.frontend_url') . '/payment-success?order_id=' . $merchantOrderId
        );

        if (! isset($response['redirectUrl'])) {
            return response()->json([
                'success'  => false,
                'response' => $response,
            ], 400);
        }

        return response()->json([
            'success'      => true,
            'order_id'     => $merchantOrderId,
            'checkout_url' => $response['redirectUrl'],
        ]);
    }

    /**
     * Check Status
     */
    public function status($merchantOrderId)
    {
        $response = $this->phonePe->checkOrderStatus($merchantOrderId);

        $status = $response['state'] ?? 'FAILED';

        // Payment::where('merchant_order_id', $merchantOrderId)
        //     ->update(['status' => $status]);

        return response()->json([
            'order_id'      => $merchantOrderId,
            'status'        => $status,
            'full_response' => $response,
        ]);
    }
}
