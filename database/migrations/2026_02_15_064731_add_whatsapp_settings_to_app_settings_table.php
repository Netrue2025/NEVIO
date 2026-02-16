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
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('whatsapp_token')->nullable()->after('whatsapp_message_cost');
            $table->string('whatsapp_phone_number_id')->nullable()->after('whatsapp_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_token', 'whatsapp_phone_number_id']);
        });
    }
};
