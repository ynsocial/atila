<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedName extends Model
{
    protected $table = 'blocked_names';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'reason',
        'added_by',
        'created_at',
    ];
}

