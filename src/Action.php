<?php

namespace Brix\Core;

use Brix\Core\Broker\Broker;
use Brix\Core\Broker\AiHelper\BrokerAiPrepareAction;
use Brix\Core\Broker\CliDoCmd;
use Phore\Cli\Input\In;
use Phore\Cli\Output\Out;
use Phore\FileSystem\PhoreFile;

class Action extends AbstractBrixCommand
{

    private PhoreFile $actionFile;
    public function __construct()
    {
        parent::__construct();
        $this->actionFile = phore_file("./CUR-BRIX-ACTION.yml");
    }

    public function list()
    {
        $broker = Broker::getInstance();
        $actions = $broker->listActions();

        Out::Table($actions, false, ["actionName", "desc"]);
    }



    public function create($argv, string $contextId = null) {

        $broker = Broker::getInstance();
        $aiPrepare = new BrokerAiPrepareAction($broker);

        $description = implode(" ", $argv);

        $actionName = $aiPrepare->selectActionByDescription($description);
        if ($actionName === null) {
            Out::TextDanger("Cannot detect action.");
            return;
        }
        Out::TextSuccess("Detected action: $actionName");

        $data = $aiPrepare->createActionStruct($actionName, $description, $contextId);

        $this->actionFile->set_yaml($data);
        if ( ! In::AskBool("Action created in File $this->actionFile. Perform?.", true))
            return;

        $actionData = $this->actionFile->get_yaml($broker->getActionInfo($actionName)->inputClassName);
        print_r ($actionData);
        $broker->performAction($actionData);




        $actionName = array_shift($argv);

    }


}
