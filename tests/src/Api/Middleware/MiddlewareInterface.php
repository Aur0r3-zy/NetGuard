<?php
namespace Api\Middleware;

interface MiddlewareInterface {
    public function handle($request, $next);
} 