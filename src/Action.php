<?php

namespace Leuffen\Brix;

use Brix\Core\Broker\Broker;
use Leuffen\Brix\Business\AbstractBrixCommand;
use Phore\Cli\Output\Out;

class Action extends AbstractBrixCommand
{


    public function list()
    {
        $broker = Broker::getInstance();
        $actions = $broker->listActions();

        Out::Table($actions, $actions);
    }

}
