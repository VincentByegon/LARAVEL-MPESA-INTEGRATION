<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type')->default('Buy Goods');
            $table->string('trans_id')->unique();               // M-Pesa Transaction ID e.g. RHI7HJ8K9L
            $table->string('trans_time');                       // 20240115103045
            $table->decimal('trans_amount', 10, 2);            // Amount paid
            $table->string('business_short_code');             // Your till number
            $table->string('bill_ref_number')->nullable();     // Account reference (if any)
            $table->string('invoice_number')->nullable();
            $table->decimal('org_account_balance', 10, 2)->nullable();
            $table->string('third_party_trans_id')->nullable();
            $table->string('msisdn');                           // Customer phone number
            $table->string('first_name')->nullable();           // Customer first name
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status')->default('completed');    // completed / failed
            $table->json('raw_payload')->nullable();           // Full Safaricom JSON
            $table->timestamps();

            $table->index('trans_id');
            $table->index('msisdn');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_payments');
    }
};