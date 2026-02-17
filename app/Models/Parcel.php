<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Parcel extends Model
{
    protected $fillable = [
        'tracking_number',
        'sender_id',
        'origin_town',
        'recipient_id',
        'destination_town',
        'destination_address',
        'amount',
        'status',
        'payment_phone' // Add this line
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($parcel) {
            $parcel->tracking_number = 'JET' . strtoupper(Str::random(6));
        });
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function mpesaTransactions()
    {
        return $this->hasMany(MpesaTransaction::class);
    }
}