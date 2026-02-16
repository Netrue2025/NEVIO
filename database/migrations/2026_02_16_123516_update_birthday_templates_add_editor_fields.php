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
        Schema::table('birthday_templates', function (Blueprint $table) {
            $table->json('elements')->nullable()->after('background_image');
            $table->string('thumbnail')->nullable()->after('elements');
            $table->dropColumn('layout_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birthday_templates', function (Blueprint $table) {
            $table->json('layout_config')->nullable()->after('background_image');
            $table->dropColumn(['elements', 'thumbnail']);
        });
    }
};
