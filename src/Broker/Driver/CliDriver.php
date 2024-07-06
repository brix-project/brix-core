<?php

namespace Brix\Core\Broker\Driver;

use Brix\Core\Broker\Broker;

class CliDriver
{

    public function __construct(public readonly Broker $broker) {

    }


    public function listActions() : array
    {
        $actions = [];
        foreach ($this->broker->actions as $actionName => $callback) {
            $actions[] = $actionName;
        }
        return $actions;
    }







}
