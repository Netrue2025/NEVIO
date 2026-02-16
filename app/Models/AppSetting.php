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
        'whatsapp_message_cost',
        'whatsapp_token',
        'whatsapp_phone_number_id',
    ];

    protected $casts = [
        'sms_price_per_message' => 'float',
        'whatsapp_message_cost' => 'float',
    ];
}


