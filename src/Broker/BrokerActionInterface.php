<?php

namespace Brix\Core\Broker;

use Brix\Core\Broker\Logger\Logger;
use Brix\Core\Broker\Message\ContextMsg;

interface BrokerActionInterface
{

    public function getName() : string;

    public function getDescription() : string;

    public function getInputClass() : string;

    public function getOutputClass() : string;

    public function getStateClass() : string;

    public function needsContext() : bool;

    public function performAction(object $input, Broker $broker, Logger $logger, ?string $contextId) : BrokerActionResponse;

}
