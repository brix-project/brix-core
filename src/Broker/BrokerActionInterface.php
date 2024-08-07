<?php

namespace Brix\Core\Broker;

use Brix\Core\Broker\Log\Logger;
use Brix\Core\Broker\Message\ContextMsg;

interface BrokerActionInterface
{

    public function getName() : string;

    public function getDescription() : string;

    public function getInputClass() : string;

    /**
     * Perfrom additional Prepare Step before the data is made available to the user.
     * @return array|null
     */
    public function performPreAction(object $input, Broker $broker, Logger $logger, ?string $contextId) : object;



    public function getStateClass() : string;

    public function needsContext() : bool;

    public function performAction(object $input, Broker $broker, Logger $logger, ?string $contextId) : BrokerActionResponse;

}
