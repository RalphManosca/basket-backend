<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Calculate total amount
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            // Create order
            $order = Order::create([
                'user_email' => $request->user_email,
                'total_amount' => $totalAmount,
                'status' => 'completed',
            ]);

            // Create order items
            foreach ($request->items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            DB::commit();

            // Load items relationship
            $order->load('items');

            // Send order confirmation email
            $this->sendOrderEmail($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'user_email' => $order->user_email,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                        ];
                    }),
                    'created_at' => $order->created_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
            ], 500);
        }
    }

    public function show(Order $order): JsonResponse
    {
        $order->load('items');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'user_email' => $order->user_email,
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                    ];
                }),
                'created_at' => $order->created_at->toIso8601String(),
            ],
        ]);
    }

    private function sendOrderEmail(Order $order): void
    {
        try {
            $emailServiceUrl = env('EMAIL_SERVICE_URL', 'http://email-service');

            Http::timeout(5)->post("{$emailServiceUrl}/api/email/send-order", [
                'order_id' => $order->id,
                'user_email' => $order->user_email,
                'order_details' => [
                    'id' => $order->id,
                    'total_amount' => $order->total_amount,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_name' => $item->product_name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                        ];
                    })->toArray(),
                ],
            ]);

            Log::info("Order confirmation email sent for order #{$order->id}");
        } catch (\Exception $e) {
            // Log error but don't fail the order creation
            Log::error("Failed to send order email: " . $e->getMessage());
        }
    }
}
