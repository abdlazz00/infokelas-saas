<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expired_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
