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
        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('twillo_uk_phone_from')->nullable();
            $table->string('twillo_us_phone_from')->nullable();
            $table->string('africa_tallking_phone_from')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
               $table->dropColumn('twillo_uk_phone_from')->nullable();
            $table->dropColumn('twillo_us_phone_from')->nullable();
            $table->dropColumn('africa_tallking_phone_from')->nullable();
        });
    }
};
