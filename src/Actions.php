<?php

namespace Brix\Core;

use Brix\Core\Broker\Broker;
use Brix\Core\Broker\AiHelper\BrokerAiPrepareAction;
use Brix\Core\Broker\CliDoCmd;
use Brix\Core\Broker\Message\ContextMsg;
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

        $filter = $argv[0] ?? null;
        
        $selectedContextId = Broker::getInstance()->getSelectedContextId();
        if ($selectedContextId !== null) {
            Out::TextWarning("**Current Context.......: $selectedContextId**");
        }
        $contextList = Broker::getInstance()->getContextStorageDriver()->listContexts($filter);
        
       
        if (count($contextList) === 0) {
            Out::TextWarning("No contexts found.");
            return;
        }
        Out::TextInfo("**Available Contexts:**");
        Out::Table($contextList, false, ["contextId", "shortInfo"]);
        if (count($contextList) > 1) {
            
            return;
        }
        $contextId = $contextList[0]["contextId"];
        
        if ($contextId !== $filter) {
            In::AskBool("Switch to context '$contextId'?", true);
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
        if ($contextId === null) {
            $selectedContextId = Broker::getInstance()->getSelectedContextId();
            if ($selectedContextId !== null) {
                Out::TextWarning("**Selected Context.......: $selectedContextId**");
            }
            $contextId = $selectedContextId;
        }

        $tempFile = phore_file("CUR-CONTEXT.yml");
        $tempFile->unlinkOnClose();

        $broker = Broker::getInstance();
        $contextData = $broker->getContextStorageDriver()->withContext($contextId)->getData();
        $tempFile->set_yaml($contextData);

        Out::TextSuccess("Context data written to CUR-CONTEXT.yml");
        if ( ! In::AskBool("Save changes?", true)) {
            Out::TextDanger("Abort");
            return;
        }



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
        $contextId = $file->getDirname()->getBasename();

        if (! preg_match("/^K([0-9]+)\-([a-z0-9\-]+)$/", $contextId, $matches))
            throw new \InvalidArgumentException("Invalid contextId: $contextId");

        $message = new ContextMsg("crm.customer_data", "The billing address of the customer. Use as main address if nothing other is specified", $file->get_yaml());
        $subMessage = new ContextMsg("subscription_id", "The subscription_id for this customer.", $matches[2] . "-k" . $matches[1]);
        $shortInfo = ($message->value["address"] ?? "Unkown Address") . "\nEMail: " . ($message->value["email"] ?? "Unknown Email") . "\nMANUAL IMPORT!";



        $storageDriver = Broker::getInstance()->getContextStorageDriver();
        if ($storageDriver->withContext($contextId)->exists()
            && ! In::AskBool("Context already exists. Overwrite?", false))
            return;
        $storageDriver->createContext($contextId, $shortInfo);
        $storageDriver->withContext($contextId)->processContextMsg($message);
        $storageDriver->withContext($contextId)->processContextMsg($subMessage);

        $storageDriver->withContext($contextId)->processContextMsg(new ContextMsg(
            "gitops.subscription_id", "The subscription_id of the created subscription", $subMessage->value
        ));
        $storageDriver->withContext($contextId)->processContextMsg(new ContextMsg(
            "website:UNKNOWN-DOMAIN.DE", "Website created", ["domain" => "UNKNOWN-DOMAIN.de", "repo"=>"leu-web-" . $subMessage->value, "subscription_id" => $subMessage->value, "contentInfo" => ""]
        ));#
        $storageDriver->withContext($contextId)->processContextMsg(new ContextMsg(
            "internetx.domain:UNKNOWN-DOMAIN.DE", "The top-level-domain registered", ["domain" => "UNKNOWN-DOMAIN.de", "created"=>phore_datetime(), "payment_due" => "01"]
        ));
        $storageDriver->withContext($contextId)->processContextMsg(new ContextMsg(
            "mailbox-org.domain:UNKNOWN-DOMAIN.DE", "Domain is connected to mailbox.org", "UNKNOWN-DOMAIN.de"
        ));
        $storageDriver->withContext($contextId)->processContextMsg(new ContextMsg(
            "mailbox-org.mail:ALIAS@UNKNOWN-DOMAIN.DE", "Mail account created", [
                "account" => "ALIAS@UNKNOWN-DOMAIN.DE",
                "payment_due" => "01",
                "aliases" => []
            ]
        ));
        Out::TextSuccess("Context imported: $contextId");

        if (In::AskBool("Edit?", true)) {
            $this->context_edit([$contextId]);
        }
    }


    public function prepare(array $argv, bool $editor = false, bool $new = false) {

        $broker = Broker::getInstance();
        $aiPrepare = new BrokerAiPrepareAction($broker);

        $contextId = $broker->getSelectedContextId();

        $description = implode(" ", $argv);
        $tmpName = phore_file("/tmp/last_prepare_input.txt");

        if ($new)
            $tmpName->set_contents("");

        if (count($argv) === 0 || $editor) {
            passthru("editor $tmpName", $ret);
            if ($ret !== 0)
                throw new \InvalidArgumentException("Editor failed.");
            $description = $tmpName->get_contents();
            echo "Description: $description\n";
            In::AskBool("Continue with prepared data?", true);

        }

        $tmpName->set_contents($description);


        if ($contextId !== null)
            Out::TextWarning("**Selected Context:** _{$contextId}_");



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

        $data = phore_object_to_array($data);
        print_r ($data);
        $this->actionFile->set_yaml(phore_object_to_array($data));
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
