<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ClassStudent extends Pivot
{
    use HasUlids;

    protected $table = 'class_students';

    public $incrementing = false;
}
