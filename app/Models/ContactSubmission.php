<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    protected $table = 'contact_submissions';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
        'ip',
        'user_agent',
        'flags',
    ];

    protected $casts = [
        'flags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

