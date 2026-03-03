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
            if (!Schema::hasColumn('user_settings', 'twillo_uk_phone_from')) {
                $table->string('twillo_uk_phone_from')->nullable();
            }

            if (!Schema::hasColumn('user_settings', 'twillo_us_phone_from')) {
                $table->string('twillo_us_phone_from')->nullable();
            }

            if (!Schema::hasColumn('user_settings', 'africa_tallking_phone_from')) {
                $table->string('africa_tallking_phone_from')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {

            if (Schema::hasColumn('user_settings', 'twillo_uk_phone_from')) {
                $table->dropColumn('twillo_uk_phone_from');
            }

            if (Schema::hasColumn('user_settings', 'twillo_us_phone_from')) {
                $table->dropColumn('twillo_us_phone_from');
            }

            if (Schema::hasColumn('user_settings', 'africa_tallking_phone_from')) {
                $table->dropColumn('africa_tallking_phone_from');
            }
        });
    }
};
