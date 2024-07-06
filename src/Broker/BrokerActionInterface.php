<?php

namespace Brix\Core\Broker;

interface BrokerActionInterface
{

    public function getName() : string;

    public function getDescription() : string;

    public function getInputClass() : string;

    public function getOutputClass() : string;

    public function getStateClass() : string;

    public function performAction(object $input, Broker $broker) : BrokerActionResponse;

}
