<?php

namespace Brix\Core\Broker;

use Brix\Core\Broker\Log\Logger;

abstract class AbstractBrokerAction implements BrokerActionInterface
{
    public function performPreAction(object $input, Broker $broker, Logger $logger, ?string $contextId): object
    {
        return $input; // Pass thru
    }
    public function getStateClass(): string
    {
        return "";
    }

    public function needsContext(): bool
    {
        return true;
    }

}
