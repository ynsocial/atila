<?php

namespace App\Http;

use App\Http\Middleware\DetectSuspiciousPayload;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        'detect.suspicious' => DetectSuspiciousPayload::class,
    ];
}

