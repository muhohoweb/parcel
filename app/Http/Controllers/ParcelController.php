<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\User;
use App\Models\MpesaTransaction;
use Iankumu\Mpesa\Facades\Mpesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ParcelController extends Controller
{
    private function getUploadPath()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/uploads';
    }

    public function index()
    {
        $parcels = Parcel::with(['sender', 'recipient', 'mpesaTransactions' => function ($query) {
            $query->where('status', 'completed')->latest();
        }])
            ->latest()
            ->get();

        return Inertia::render('Parcels/Index', [
            'parcels' => $parcels
        ]);
    }

    public function store(Request $request)
    {
        Log::info('=== PARCEL STORE METHOD HIT ===');
        Log::info('Request method: ' . $request->method());
        Log::info('Request all data:', $request->all());
        Log::info('Has image file:', ['has_image' => $request->hasFile('image')]);
        Log::info('Files:', $request->allFiles());

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

            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'amount' => 'required|numeric|min:0',
            'payment_phone' => 'required|string|max:15',
        ], [
            'image.max' => 'The image must be less than 2MB.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a JPG, JPEG, PNG, or WEBP file.',
        ]);

        Log::info('Validation passed!');

        // Check for duplicate submission in last 5 minutes
        $recentDuplicate = Parcel::where('payment_phone', $validated['payment_phone'])
            ->where('amount', $validated['amount'])
            ->where('destination_town', $validated['destination_town'])
            ->where('created_at', '>', now()->subMinutes(5))
            ->exists();

        if ($recentDuplicate) {
            Log::warning('Duplicate parcel detected');
            return back()->withErrors(['duplicate' => 'A similar parcel was just created. Please wait a moment before submitting again.']);
        }

        // Use database transaction
        DB::beginTransaction();

        try {
            Log::info('Creating sender...');
            $sender = User::firstOrCreate(
                ['phone' => $validated['sender_phone']],
                [
                    'first_name' => $validated['sender_first_name'],
                    'last_name' => $validated['sender_last_name'],
                    'national_id_no' => $validated['sender_national_id'],
                ]
            );

            Log::info('Creating recipient...');
            $recipient = User::firstOrCreate(
                ['phone' => $validated['recipient_phone']],
                [
                    'first_name' => $validated['recipient_first_name'],
                    'last_name' => $validated['recipient_last_name'],
                    'national_id_no' => $validated['recipient_national_id'],
                ]
            );

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                Log::info('Uploading image...');
                $imagePath = $this->uploadImage($request->file('image'));
                Log::info('Image uploaded:', ['path' => $imagePath]);
            }

            Log::info('Creating parcel...');
            $parcel = Parcel::create([
                'sender_id' => $sender->id,
                'origin_town' => $validated['origin_town'],
                'recipient_id' => $recipient->id,
                'destination_town' => $validated['destination_town'],
                'destination_address' => $validated['destination_address'],
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'amount' => $validated['amount'],
                'payment_phone' => $validated['payment_phone'],
            ]);

            Log::info('Parcel created:', ['id' => $parcel->id, 'tracking' => $parcel->tracking_number]);

            // Initiate M-Pesa STK Push
            Log::info('Initiating M-Pesa STK push...');
            $stkResult = $this->initiateStkPush($parcel, $validated['payment_phone'], $validated['amount']);

            DB::commit();

            Log::info('Transaction committed successfully');

            return back()->with('success', $stkResult['message']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Parcel Creation Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->withErrors(['error' => 'Failed to create parcel. Please try again.']);
        }
    }

    private function uploadImage($image): string
    {
        $uploadPath = $this->getUploadPath();

        // Ensure directory exists
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $filename = time() . '_' . Str::random(20) . '.' . $image->getClientOriginalExtension();
        $image->move($uploadPath, $filename);

        return 'uploads/' . $filename;
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
                amount: (int)$amount,
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

    private function sendWhatsAppMessage(string $phone, string $message): void
    {
        Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.flaresend.key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.flaresend.com/send-message', [
            'recipients' => [$phone],
            'type' => 'text',
            'text' => $message,
        ]);
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

            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',

            'amount' => 'required|numeric|min:0',
            'payment_phone' => 'required|string|max:15',
            'status' => 'required|in:pending_payment,received,in_transit,delivered',
        ]);

        DB::beginTransaction();

        try {
            $sender = User::firstOrCreate(
                ['phone' => $validated['sender_phone']],
                [
                    'first_name' => $validated['sender_first_name'],
                    'last_name' => $validated['sender_last_name'],
                    'national_id_no' => $validated['sender_national_id'],
                ]
            );

            if ($sender->wasRecentlyCreated === false) {
                $sender->update([
                    'first_name' => $validated['sender_first_name'],
                    'last_name' => $validated['sender_last_name'],
                    'national_id_no' => $validated['sender_national_id'],
                ]);
            }

            $recipient = User::firstOrCreate(
                ['phone' => $validated['recipient_phone']],
                [
                    'first_name' => $validated['recipient_first_name'],
                    'last_name' => $validated['recipient_last_name'],
                    'national_id_no' => $validated['recipient_national_id'],
                ]
            );

            if ($recipient->wasRecentlyCreated === false) {
                $recipient->update([
                    'first_name' => $validated['recipient_first_name'],
                    'last_name' => $validated['recipient_last_name'],
                    'national_id_no' => $validated['recipient_national_id'],
                ]);
            }

            $imagePath = $parcel->image_path;

            if (!empty($validated['remove_image']) && $parcel->image_path) {
                $uploadPath = $this->getUploadPath();
                $oldImagePath = $uploadPath . '/' . basename($parcel->image_path);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
                $imagePath = null;
            }

            if ($request->hasFile('image')) {
                if ($parcel->image_path) {
                    $uploadPath = $this->getUploadPath();
                    $oldImagePath = $uploadPath . '/' . basename($parcel->image_path);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imagePath = $this->uploadImage($request->file('image'));
            }

            $parcel->update([
                'sender_id' => $sender->id,
                'origin_town' => $validated['origin_town'],
                'recipient_id' => $recipient->id,
                'destination_town' => $validated['destination_town'],
                'destination_address' => $validated['destination_address'],
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'amount' => $validated['amount'],
                'payment_phone' => $validated['payment_phone'],
                'status' => $validated['status'],
            ]);

            if ($validated['status'] === 'delivered') {
                $recipientPhone = '254' . ltrim($recipient->phone, '0');

                Log::info('Sending WhatsApp to: ' . $recipientPhone);

                $message = "Hello {$recipient->first_name}, your parcel ({$parcel->tracking_number}) "
                    . "from {$parcel->origin_town} has been delivered to {$parcel->destination_address}. "
                    . "Thank you for using JetQuickly!";

                $this->sendWhatsAppMessage($recipientPhone, $message);

                Log::info('WhatsApp message sent');
            }

            DB::commit();

            return back()->with('success', 'Parcel updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Parcel Update Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update parcel. Please try again.']);
        }
    }

    public function destroy(Parcel $parcel)
    {
        // Delete image if exists
        if ($parcel->image_path) {
            $uploadPath = $this->getUploadPath();
            $imagePath = $uploadPath . '/' . basename($parcel->image_path);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $parcel->delete();

        return back();
    }
}