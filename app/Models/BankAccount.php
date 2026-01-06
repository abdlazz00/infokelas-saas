<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use hasUlids;
    protected $guarded =['id'];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
