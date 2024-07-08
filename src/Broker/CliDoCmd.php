<?php

namespace Brix\Core\Broker;

use Phore\Cli\Input\In;
use Phore\FileSystem\PhoreTempFile;

class CliDoCmd
{




    public function run(array $argv, string $contextId=null) {

        $actionName = array_shift($argv);
        $argumentString = implode(" ", $argv);

        $broker = Broker::getInstance();
        $broker->getActionInfo($actionName);
        $actionInfo = $broker->getActionInfo($actionName);

        if (trim ($argumentString) === "")
            $argumentString = In::AskMultiLine("Please enter the arguments for action '$actionName':");

        $context = [];
        if ($contextId !== null)
            $context = $broker->contextStorageDriver->getData($contextId);

        $data = $broker->brixEnv->getOpenAiQuickFacet()->promptData(__DIR__ . "/do-prompt.txt", [
            "output_schema" => $actionInfo->inputSchema,
            "argument_string" => $argumentString,
            "context_json" => json_encode($context)
        ], $actionInfo->inputClassName, true);

        $tmpFile = new PhoreTempFile("ed", "yml");
        $tmpFile->set_yaml((array)$data);
        passthru("vim " . $tmpFile->getUri());

        if (! In::AskBool("Execute this job?", true))
            return;


        $data = $tmpFile->get_yaml($actionInfo->inputClassName);

        $response = $broker->performAction($actionName, $data, $context);




    }

}
