<?php

namespace filter;

use Closure;

interface HandlerInterface
{
    public function handler($request, Closure $next);
}