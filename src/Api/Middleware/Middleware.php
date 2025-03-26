<?php
namespace App\Api\Middleware;

abstract class Middleware {
    abstract public function handle(string $request, string $method): void;
} 