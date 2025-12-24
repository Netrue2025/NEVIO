<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_email',
        'from_phone',
        'twillo_uk_phone_from',
        'twillo_us_phone_from',
        'africa_tallking_phone_from'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


