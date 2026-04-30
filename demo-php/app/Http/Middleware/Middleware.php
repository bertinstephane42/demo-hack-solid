<?php

namespace App\Http\Middleware;

use Core\Request;
use Core\Response;

interface Middleware
{
    public function handle(Request $request): ?Response;
}
