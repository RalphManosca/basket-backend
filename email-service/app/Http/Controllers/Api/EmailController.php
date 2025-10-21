<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmation;
use App\Models\EmailLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function sendOrder(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer',
            'user_email' => 'required|email',
            'order_details' => 'required|array',
        ]);

        try {
            Mail::to($request->user_email)
                ->send(new OrderConfirmation($request->order_details));

            EmailLog::create([
                'order_id' => $request->order_id,
                'recipient' => $request->user_email,
                'status' => 'sent',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Email failed: ' . $e->getMessage());

            EmailLog::create([
                'order_id' => $request->order_id,
                'recipient' => $request->user_email,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email delivery failed',
            ], 500);
        }
    }
}
