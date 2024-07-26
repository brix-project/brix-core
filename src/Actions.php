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
use Phore\FileSystem\PhoreTempFile;

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
            $selectedContextId = Broker::getInstance()->getSelectedContextId();
            if ($selectedContextId !== null) {
                Out::TextWarning("**Selected Context.......: $selectedContextId**");
            }
            Out::Table(Broker::getInstance()->getContextStorageDriver()->listContexts(), false, ["contextId", "shortInfo"]);
            exit(1);
        }
        $broker = Broker::getInstance();
        if ( ! $broker->getContextStorageDriver()->withContext($contextId)->exists())
            throw new \InvalidArgumentException("ContextId not found: $contextId");
        $broker->selectContextId($contextId);
        Out::TextSuccess("**Context selected:** _{$contextId}_ \n");
        Out::TextInfo($broker->getContextStorageDriver()->withContext($contextId)->getData()["__shortInfo"] ?? "");
    }


    public function context_edit($argv) {
        $contextId = $argv[0] ?? null;
        $tempFile = phore_file("CUR-CONTEXT.yml");
        $tempFile->unlinkOnClose();

        $broker = Broker::getInstance();
        $contextData = $broker->getContextStorageDriver()->withContext($contextId)->getData();
        $tempFile->set_yaml($contextData);

        Out::TextSuccess("Context data written to CUR-CONTEXT.yml");
        In::AskBool("Save changes on exit?", true);



        if (trim($tempFile->get_contents()) === "") {
            if ( ! In::AskBool("Empty context data. Delete context?", true)) {
                return;
            }
            $broker->getContextStorageDriver()->rmContext($contextId);
            return;
        }
        $contextData = $tempFile->get_yaml();
        $broker->getContextStorageDriver()->withContext($contextId)->setData($contextData);
        Out::TextSuccess("Context data saved.");
    }


    public function context_import($argv) {
        $fileName = $argv[0] ?? null;
        if ($fileName === null)
            throw new \InvalidArgumentException("No filename given.");

        $file = phore_file($fileName);
        $contextId = $file->getBasename(".yml");

        if (! preg_match("/^K([0-9]+)\-([a-z0-9\-]+)$/", $contextId))
            throw new \InvalidArgumentException("Invalid contextId: $contextId");

        $contextData = $file->get_yaml();
        Broker::getInstance()->getContextStorageDriver()->createContext($contextId, $contextData["__shortInfo"] ?? "");
        Broker::getInstance()->getContextStorageDriver()->withContext($contextId)->setData($contextData);
        Out::TextSuccess("Context imported: $contextId");
    }


    public function prepare(array $argv) {

        $broker = Broker::getInstance();
        $aiPrepare = new BrokerAiPrepareAction($broker);

        $contextId = $broker->getSelectedContextId();



        if ($contextId !== null)
            Out::TextWarning("**Selected Context:** _{$contextId}_");

        $description = implode(" ", $argv);

        $actionName = $aiPrepare->selectActionByDescription($description);
        if ($actionName === null) {
            Out::TextDanger("Cannot detect action.");
            return;
        }
        Out::TextSuccess("Detected action: $actionName");

        $actionInfo = $broker->getActionInfo($actionName);
        if ( ! $actionInfo->needsContext)
            $contextId = null;


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
