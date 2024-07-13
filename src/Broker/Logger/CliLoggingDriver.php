<?php

namespace Brix\Core\Broker\Logger;

use Phore\Cli\Output\Out;

class CliLoggingDriver implements LoggingDriver
{

    public function log(string $message, LoggingLevel $level, array $context = []): void
    {
        
        switch ($level) {
            case LoggingLevel::DEBUG:
                Out::TextInfo("[DEBUG] $message");
                break;
            case LoggingLevel::INFO:
                Out::TextInfo("[INFO] $message");
                break;
            case LoggingLevel::NOTICE:
                Out::TextInfo("[NOTICE] $message");
                break;
            case LoggingLevel::WARNING:
                Out::TextWarning("[WARNING] $message");
                break;
            case LoggingLevel::ERROR:
                Out::TextError("[ERROR] $message");
                break;
            case LoggingLevel::SUCCESS:
                Out::TextSuccess("[SUCCESS] $message");
                break;
                
            default:
                throw new \InvalidArgumentException("Unknown log level: $level");
        }
    }
}