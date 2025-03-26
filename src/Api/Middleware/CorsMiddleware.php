<?php

namespace App\Api\Middleware;

class CorsMiddleware extends Middleware {
    public function handle(string $request, string $method): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($method === 'OPTIONS') {
            exit(0);
        }
    }
} 