<?php

namespace Brix\Core\Broker\Log;

class Logger
{

    public function __construct(private ?LoggingDriver $driver = null) {
        if ($this->driver === null)
            $this->driver = new CliLoggingDriver();
    }

    public function logInfo($message, $context=[]) {
        $this->driver->log($message, LoggingLevel::INFO, $context);
    }
    
    public function logDebug($message, $context=[]) {
        $this->driver->log($message, LoggingLevel::DEBUG, $context);
    }
    
    public function logNotice($message, $context=[]) {
        $this->driver->log($message, LoggingLevel::NOTICE, $context);
    }
    
    public function logWarning($message, $context=[]) {
        $this->driver->log($message, LoggingLevel::WARNING, $context);
    }
    
    public function logError($message, $context=[]) {
        $this->driver->log($message, LoggingLevel::ERROR, $context);
    }
    
    public function logSuccess($message, $context=[]) {
        $this->driver->log($message, LoggingLevel::SUCCESS, $context);
    }
}
