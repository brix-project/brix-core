<?php

namespace Brix\Core\Broker\Log;

interface LoggingDriver
{

    
    public function log(string $message, LoggingLevel $level, array $context = []): void;
    
}