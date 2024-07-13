<?php

namespace Brix\Core\Broker\Logger;

interface LoggingDriver
{

    
    public function log(string $message, LoggingLevel $level, array $context = []): void;
    
}