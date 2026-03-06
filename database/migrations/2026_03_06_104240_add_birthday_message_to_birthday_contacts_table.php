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
        Schema::table('birthday_contacts', function (Blueprint $table) {
            $table->string('birthday_message')->after('photo_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birthday_contacts', function (Blueprint $table) {
            $table->dropColumn('birthday_message')->nullable();
        });
    }
};
