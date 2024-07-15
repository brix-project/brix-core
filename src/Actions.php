<?php

namespace Brix\Core;

use Brix\Core\Broker\Broker;
use Brix\Core\Broker\AiHelper\BrokerAiPrepareAction;
use Brix\Core\Broker\CliDoCmd;
use Brix\MailSpool\Mailspool;
use Brix\MailSpool\MailSpoolFacet;
use Phore\Cli\Input\In;
use Phore\Cli\Output\Out;
use Phore\FileSystem\PhoreFile;

class Actions extends AbstractBrixCommand
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


    public function context($argv) {

        $contextId = $argv[0] ?? null;
        if ($contextId === null) {
            Out::Table(Broker::getInstance()->contextStorageDriver->listContexts(), false, ["contextId", "shortInfo"]);
            exit(1);
        }
        $broker = Broker::getInstance();
        if ( ! $broker->contextStorageDriver->withContext($contextId)->exists())
            throw new \InvalidArgumentException("ContextId not found: $contextId");
        $this->brixEnv->getState("action")->set("selected_context_id", $contextId);
        Out::TextSuccess("**Context selected:** _{$contextId}_ \n");
        Out::TextInfo($broker->contextStorageDriver->withContext($contextId)->getData()["__shortInfo"] ?? "");
    }

    public function prepare(array $argv) {

        $broker = Broker::getInstance();
        $aiPrepare = new BrokerAiPrepareAction($broker);

        $contextId = $this->brixEnv->getState("action")->get("selected_context_id");

        print_r ($contextId);
        if ($contextId !== null)
            Out::TextWarning("**Selected Context:** _{$contextId}_");

        $description = implode(" ", $argv);

        $actionName = $aiPrepare->selectActionByDescription($description);
        if ($actionName === null) {
            Out::TextDanger("Cannot detect action.");
            return;
        }
        Out::TextSuccess("Detected action: $actionName");

        $data = $aiPrepare->createActionStruct($actionName, $description, $contextId);

        $this->actionFile->set_yaml($data);
        Out::TextInfo("\n\n" . $this->actionFile->get_contents());
        if ( ! In::AskBool("Action created in File $this->actionFile. Perform?.", true))
            return;

        $this->perform();

    }

    public function perform() {
        $broker = Broker::getInstance();
        $actionData = $this->actionFile->get_yaml();
        $actionName = $actionData["action_name"];
        $actionData = $this->actionFile->get_yaml($broker->getActionInfo($actionName)->inputClassName);
        $result = $broker->performAction($actionData);

        if ($result->type === "success") {
            Out::TextSuccess("Action performed successfully: ". $result->message);
        } else {
            Out::TextDanger("Action failed: " . $result->message);
        }
        
        if (MailSpoolFacet::getInstance()->hasUnsentMails()) {
            if (In::AskBool("Mails are spooled. Send all spooled mails?", true)) {
                MailSpoolFacet::getInstance()->sendMail();
            }
        }
        
    }


}
