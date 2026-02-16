<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\User;
use App\Models\MpesaTransaction;
use Iankumu\Mpesa\Facades\Mpesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ParcelController extends Controller
{
    public function index()
    {
        $parcels = Parcel::with(['sender', 'recipient'])
            ->latest()
            ->get();

        return Inertia::render('Parcels/Index', [
            'parcels' => $parcels
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sender_first_name' => 'required|string|max:255',
            'sender_last_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:15',
            'sender_national_id' => 'required|string|max:20',
            'origin_town' => 'required|string|max:255',

            'recipient_first_name' => 'required|string|max:255',
            'recipient_last_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:15',
            'recipient_national_id' => 'required|string|max:20',
            'destination_town' => 'required|string|max:255',
            'destination_address' => 'required|string',

            'amount' => 'required|numeric|min:0',
            'payment_phone' => 'required|string|max:15',
        ]);

        $sender = User::firstOrCreate(
            ['phone' => $validated['sender_phone']],
            [
                'first_name' => $validated['sender_first_name'],
                'last_name' => $validated['sender_last_name'],
                'national_id_no' => $validated['sender_national_id'],
            ]
        );

        $recipient = User::query()->firstOrCreate(
            ['phone' => $validated['recipient_phone']],
            [
                'first_name' => $validated['recipient_first_name'],
                'last_name' => $validated['recipient_last_name'],
                'national_id_no' => $validated['recipient_national_id'],
            ]
        );

        $parcel = Parcel::create([
            'sender_id' => $sender->id,
            'origin_town' => $validated['origin_town'],
            'recipient_id' => $recipient->id,
            'destination_town' => $validated['destination_town'],
            'destination_address' => $validated['destination_address'],
            'amount' => $validated['amount'],
            'payment_phone' => $validated['payment_phone'],
        ]);

        // Initiate M-Pesa STK Push
        $stkResult = $this->initiateStkPush($parcel, $validated['payment_phone'], $validated['amount']);

        return back()->with('success', $stkResult['message']);
    }

    private function initiateStkPush(Parcel $parcel, string $phoneNumber, float $amount): array
    {
        // Format phone: 07xxxxxxxx -> 2547xxxxxxxx
        $phone = preg_replace('/^0/', '254', $phoneNumber);
        $phone = preg_replace('/^\+/', '', $phone);

        $accountReference = $parcel->tracking_number;

        try {
            $response = Mpesa::stkpush(
                phonenumber: $phone,
                amount: (int) $amount,
                account_number: $accountReference,
                callbackurl: route('mpesa.callback'),
                transactionType: 'CustomerPayBillOnline'
            );

            $result = $response->json();

            Log::info('M-Pesa STK Push Response', $result);

            if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
                // Store transaction
                MpesaTransaction::create([
                    'parcel_id' => $parcel->id,
                    'merchant_request_id' => $result['MerchantRequestID'],
                    'checkout_request_id' => $result['CheckoutRequestID'],
                    'phone_number' => $phone,
                    'amount' => $amount,
                    'account_reference' => $accountReference,
                    'status' => 'pending',
                ]);

                return [
                    'success' => true,
                    'message' => 'M-Pesa payment prompt sent to ' . $phoneNumber,
                ];
            }

            return [
                'success' => false,
                'message' => $result['ResponseDescription'] ?? 'Failed to send M-Pesa prompt',
            ];

        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Payment service temporarily unavailable',
            ];
        }
    }

    public function update(Request $request, Parcel $parcel)
    {
        $validated = $request->validate([
            'sender_first_name' => 'required|string|max:255',
            'sender_last_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:15',
            'sender_national_id' => 'required|string|max:20',
            'origin_town' => 'required|string|max:255',

            'recipient_first_name' => 'required|string|max:255',
            'recipient_last_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:15',
            'recipient_national_id' => 'required|string|max:20',
            'destination_town' => 'required|string|max:255',
            'destination_address' => 'required|string',

            'amount' => 'required|numeric|min:0',
            'payment_phone' => 'required|string|max:15',
            'status' => 'required|in:pending_payment,received,in_transit,delivered',
        ]);

        // Update or create sender
        $sender = User::firstOrCreate(
            ['phone' => $validated['sender_phone']],
            [
                'first_name' => $validated['sender_first_name'],
                'last_name' => $validated['sender_last_name'],
                'national_id_no' => $validated['sender_national_id'],
            ]
        );

        // If sender exists, update their details
        if ($sender->wasRecentlyCreated === false) {
            $sender->update([
                'first_name' => $validated['sender_first_name'],
                'last_name' => $validated['sender_last_name'],
                'national_id_no' => $validated['sender_national_id'],
            ]);
        }

        // Update or create recipient
        $recipient = User::firstOrCreate(
            ['phone' => $validated['recipient_phone']],
            [
                'first_name' => $validated['recipient_first_name'],
                'last_name' => $validated['recipient_last_name'],
                'national_id_no' => $validated['recipient_national_id'],
            ]
        );

        // If recipient exists, update their details
        if ($recipient->wasRecentlyCreated === false) {
            $recipient->update([
                'first_name' => $validated['recipient_first_name'],
                'last_name' => $validated['recipient_last_name'],
                'national_id_no' => $validated['recipient_national_id'],
            ]);
        }

        // Update parcel
        $parcel->update([
            'sender_id' => $sender->id,
            'origin_town' => $validated['origin_town'],
            'recipient_id' => $recipient->id,
            'destination_town' => $validated['destination_town'],
            'destination_address' => $validated['destination_address'],
            'amount' => $validated['amount'],
            'payment_phone' => $validated['payment_phone'],
            'status' => $validated['status'],
        ]);

        return back();
    }

    public function destroy(Parcel $parcel)
    {
        $parcel->delete();

        return back();
    }
}