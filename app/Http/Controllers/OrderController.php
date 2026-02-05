<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'                     => 'required|exists:users,id',
            'address_id'                  => 'required|exists:addresses,id',

            'payment.method'              => 'required|string',
            'payment.razorpay_order_id'   => 'nullable|string',
            'payment.razorpay_payment_id' => 'nullable|string',
            'payment.amount'              => 'required|numeric|min:1',

            'price_details.subtotal'      => 'required|numeric|min:1',
            'price_details.discount'      => 'nullable|numeric|min:0',
            'price_details.coupon_code'   => 'nullable|string',
            'price_details.total_amount'  => 'required|numeric|min:1',

            'items'                       => 'required|array|min:1',
            'items.*.product_id'          => 'required|exists:products,id',
            'items.*.quantity'            => 'required|integer|min:1',
            'items.*.price'               => 'required|numeric|min:1',
            'items.*.total'               => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        // ✅ Create Order
        $order = Order::create([
            'user_id'             => $request->user_id,
            'address_id'          => $request->address_id,
            'payment_method'      => $request->payment['method'],
            'razorpay_order_id'   => $request->payment['razorpay_order_id'] ?? null,
            'razorpay_payment_id' => $request->payment['razorpay_payment_id'] ?? null,
            'subtotal'            => $request->price_details['subtotal'],
            'discount'            => $request->price_details['discount'] ?? 0,
            'coupon_code'         => $request->price_details['coupon_code'],
            'total_amount'        => $request->price_details['total_amount'],
            'status'              => 'paid',
        ]);

        // ✅ Save Order Items
        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'price'      => $item['price'],
                'total'      => $item['total'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data'    => $order->load('items'),
        ]);
    }

    /* ================= GET USER ORDERS ================= */
    public function index(Request $request)
    {
        $orders = Order::with([
            'items.product.images', // 👈 include product images
        ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        // Optional: add full image_url
        $orders->each(function ($order) {
            $order->items->each(function ($item) {
                if ($item->product && $item->product->images) {
                    $item->product->images->each(function ($img) {
                        $img->image_url = asset('storage/' . $img->image_path);
                    });
                }
            });
        });

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    /* ================= SHOW ORDER ================= */

    public function show($id)
    {
        $order = Order::with([
            'items.product.images', // 👈 THIS is the key line
        ])->findOrFail($id);

        // (optional) append full image URL
        $order->items->each(function ($item) {
            if ($item->product && $item->product->images) {
                $item->product->images->each(function ($img) {
                    $img->image_url = asset('storage/' . $img->image_path);
                });
            }
        });

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    public function getMyOrderDetails(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with([
                'items.product:id,name',
            ])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'              => $order->id,
                'order_status'    => $order->order_status,
                'subtotal'        => $order->subtotal,
                'discount_amount' => $order->discount ?? 0,
                'total_amount'    => $order->total_amount,
                'createdAt'       => $order->created_at,

                'items'           => $order->items->map(function ($item) {
                    return [
                        'id'       => $item->id,
                        'quantity' => $item->quantity,
                        'price'    => $item->price,

                        'product'  => [
                            'name' => $item->product?->name,
                        ],
                    ];
                }),
            ],
        ]);
    }

    /* ================= DELETE ORDER ================= */
    public function destroy($id)
    {
        Order::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted',
        ]);
    }
}
