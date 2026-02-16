<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Models\Parcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function callback(Request $request)
    {
        Log::info('=== M-PESA CALLBACK RECEIVED ===');
        Log::info('Raw content: ' . $request->getContent());

        $callback = $request->input('Body.stkCallback');

        if (!$callback) {
            Log::warning('Invalid callback - no stkCallback found');
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid']);
        }

        $checkoutRequestId = $callback['CheckoutRequestID'];
        $resultCode = $callback['ResultCode'];
        $resultDesc = $callback['ResultDesc'];

        Log::info('Callback details', [
            'checkout_request_id' => $checkoutRequestId,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
        ]);

        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (!$transaction) {
            Log::warning('Transaction not found: ' . $checkoutRequestId);
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $metadata = [];
        foreach ($callback['CallbackMetadata']['Item'] ?? [] as $item) {
            $metadata[$item['Name']] = $item['Value'] ?? null;
        }

        Log::info('Callback metadata', $metadata);

        $transaction->update([
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'mpesa_receipt_number' => $metadata['MpesaReceiptNumber'] ?? null,
            'status' => $resultCode == 0 ? 'completed' : 'failed',
            'callback_data' => $callback,
        ]);

        // Update parcel status if payment successful
        if ($resultCode == 0 && $transaction->parcel) {
            $transaction->parcel->update([
                'status' => 'received',
            ]);
            Log::info('Parcel status updated to received: ' . $transaction->parcel->tracking_number);
        }

        Log::info('=== M-PESA CALLBACK COMPLETE ===');

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}