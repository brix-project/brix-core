<?php

namespace Brix\Core;

use Brix\Core\Broker\Broker;
use Brix\Core\Broker\CliDoCmd;
use Phore\Cli\Output\Out;

class Action extends AbstractBrixCommand
{


    public function list()
    {
        $broker = Broker::getInstance();
        $actions = $broker->listActions();

        Out::Table($actions, false, ["actionName", "desc"]);
    }



    public function do($argv, string $contextId = null) {

        $broker = Broker::getInstance();


        $cmd = new CliDoCmd($broker);
        $cmd->run($argv, $contextId);

    }


}
