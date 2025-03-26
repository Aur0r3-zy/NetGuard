<?php
namespace App\Api\Exception;

class RouteNotFoundException extends \Exception {
    public function __construct(string $message = "路由未找到", int $code = 404) {
        parent::__construct($message, $code);
    }
} 