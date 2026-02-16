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
        Schema::create('birthday_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // template name
            $table->string('background_image')->nullable();
            $table->json('layout_config'); // stores position of image, text, etc.
            // Example config: {"photo_x": 100, "photo_y": 50, "photo_width": 200, "text_x": 50, "text_y": 300}
            $table->string('background_color')->default('#ffffff');
            $table->integer('canvas_width')->default(1080);
            $table->integer('canvas_height')->default(1080);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birthday_templates');
    }
};
