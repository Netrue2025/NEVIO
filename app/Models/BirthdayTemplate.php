<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BirthdayTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'background_image',
        'background_color',
        'canvas_width',
        'canvas_height',
        'is_default',
        'elements',
        'thumbnail',
    ];

    protected $casts = [
        'elements' => 'array',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
