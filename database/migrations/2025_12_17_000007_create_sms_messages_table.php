<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('contact_number_id')
                ->nullable()
                ->constrained('contact_numbers')
                ->nullOnDelete();
            $table->string('from')->nullable();
            $table->string('to');
            $table->text('body');
            $table->string('provider')->nullable(); // africastalking, twilio, zenvia
            $table->integer('units')->default(1);
            $table->decimal('price_per_unit', 8, 4)->default(0);
            $table->decimal('total_price', 12, 4)->default(0);
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};


