<?php
namespace Api\Exception;

class UnauthorizedException extends \Exception {
    public function __construct($message = "未授权访问", $code = 401) {
        parent::__construct($message, $code);
    }
} 