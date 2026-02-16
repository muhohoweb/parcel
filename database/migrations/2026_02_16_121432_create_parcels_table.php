<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();

            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('origin_town');

            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->string('destination_town');
            $table->text('destination_address');

            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending_payment', 'received', 'in_transit', 'delivered'])->default('pending_payment');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Parcels');
    }
};