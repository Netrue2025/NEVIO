<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'sms_price_per_message',
        'currency',
    ];

    protected $casts = [
        'sms_price_per_message' => 'float',
    ];
}


