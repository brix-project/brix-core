<?php

namespace Brix\Core\Broker\AiHelper;

use Brix\Core\Broker\Broker;
use Brix\Core\Type\BrixEnv;
use Phore\Cli\Output\Out;

class BrokerAiPrepareAction
{


    public function __construct(public Broker $broker)
    {}


    public function selectActionByDescription(string $description) : ?string
    {

        $actionName = $this->broker->brixEnv->getOpenAiQuickFacet()->promptData(__DIR__ . "/selectAction-prompt.txt", [
            "defined_actions" => json_encode($this->broker->listActions()),
            "description" => $description
        ], null);

        if (trim ($actionName) === "")
            return null;
        return $actionName;

    }

    public function createActionStruct(string $actionName, string $description, ?string $contextId) : array
    {
        $actionInfo = $this->broker->getActionInfo($actionName);
        $context = [];


        if ($actionInfo->needsContext && $contextId === null) {
            throw new \InvalidArgumentException("Action requires context. Please select a context.");
        }
        if ($contextId !== null)
            $context = $this->broker->getContextStorageDriver()->withContext($contextId)->getData();

        //$altPreparePrompt = $this->broker->getAction($actionName)->performPreAction($description, $this->broker, $this->broker->logger, $contextId);

        $action = $this->broker->getAction($actionName);

        $additionalPrompt = "";
        $fragments = $action->getPrepareOptionalPromptFragments($this->broker, $this->broker->logger, $contextId);
        foreach ($fragments as $fragment) {
            $additionalPrompt .= "\n" . $fragment->toPromptString();
        }

        $data = $this->broker->brixEnv->getOpenAiQuickFacet()->promptData(__DIR__ . "/createActionStruct-prompt.txt", [
            "output_schema" => $actionInfo->inputSchema,
            "argument_string" => $description,
            "context_json" => json_encode($context),
            "alt_prepare_prompt" => $altPreparePrompt ?? "",
            "additional_prompt" => $additionalPrompt
        ], $actionInfo->inputClassName, true);

        $data = $action->performPreAction($data, $this->broker, $this->broker->logger, $contextId);

        $data = [
            "action_name" => null,
            "context_id" => null,
            ...(array)$data
        ];
        $data["action_name"] = $actionName;
        $data["context_id"] = $contextId;

        return $data;
    }


}
