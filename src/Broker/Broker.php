<?php

namespace Brix\Core\Broker;
use Brix\Core\BrixEnvFactorySingleton;
use Brix\Core\Broker\Context\FileContextStorageDriver;
use Brix\Core\Broker\Context\ObjectStoreStorageDriver;
use Brix\Core\Broker\Log\CliLoggingDriver;
use Brix\Core\Broker\Log\Logger;
use Brix\Core\Broker\Message\ContextMsg;
use Brix\Core\Type\BrixEnv;
use Lack\Keystore\KeyStore;
use Lack\OpenAi\Helper\JsonSchemaGenerator;
use Phore\ObjectStore\Driver\PhoreGoogleObjectStoreDriver;
use Phore\ObjectStore\Encryption\SodiumSyncEncryption;
use Phore\ObjectStore\ObjectStore;

class Broker
{

    public readonly BrixEnv $brixEnv;

    public readonly Logger $logger;

    private ?ObjectStoreStorageDriver $contextStorageDriver = null;

    private function __construct() {
        $this->brixEnv = BrixEnvFactorySingleton::getInstance()->getEnv();
        //$this->contextStorageDriver = new FileContextStorageDriver($this->brixEnv->rootDir . "/.context");


        $this->logger = new Logger(new CliLoggingDriver());
    }


    public function getContextStorageDriver() : ObjectStoreStorageDriver {
        if ($this->contextStorageDriver === null) {
            $accessKey = KeyStore::Get()->getAccessKey("context_store_key", true);
            $encKey = KeyStore::Get()->getAccessKey("context_store_enc_key");
            $bucketName = KeyStore::Get()->getAccessKey("context_store_bucket");

            $this->contextStorageDriver = new ObjectStoreStorageDriver(new ObjectStore(new PhoreGoogleObjectStoreDriver($accessKey, $bucketName, false, new SodiumSyncEncryption($encKey))));
        }
        return $this->contextStorageDriver;
    }


    /**
     * @var BrokerActionInterface[]
     */
    private $actions = [];


    public function registerAction(BrokerActionInterface $action) : self
    {
        if (isset($this->actions[$action->getName()]))
            throw new \InvalidArgumentException("Action with name '{$action->getName()}' already registered.");
        $this->actions[$action->getName()] = $action;
        return $this;
    }




    /**
     * @return ActionInfoType[]
     */
    public function listActions() : array
    {
        $ret = [];

        foreach ($this->actions as $actionName => $action) {
            $ret[] = $this->getActionInfo($actionName);
        }
        return $ret;
    }


    public function getActionInfo($actionName) : ActionInfoType {
         $generator = new JsonSchemaGenerator();
         $action = $this->actions[$actionName] ?? throw new \InvalidArgumentException("Action with name '$actionName' not found.");
         $inputSchema = $generator->convertToJsonSchema($action->getInputClass());
         return new ActionInfoType($actionName, $action->getDescription(), $action->getInputClass(), $action->needsContext(), $inputSchema);
    }

    public function getAction($actionName) : BrokerActionInterface {
        return $this->actions[$actionName] ?? throw new \InvalidArgumentException("Action with name '$actionName' not found.");
    }



    public function switchContext(string|null $contextId) {
        $this->contextStorageDriver = $this->getContextStorageDriver()->withContext($contextId);
    }


    public function selectContextId(string|null $contextId) {
        $this->getContextStorageDriver()->setSelectedContextId($contextId);
    }

    public function getSelectedContextId() : string|null {
        return $this->getContextStorageDriver()->getSelectedContextId();
    }


    public function performAction (object $actionData) : BrokerActionResponse {
        $actionName = $actionData->action_name ?? throw new \InvalidArgumentException("Missing 'action_name' in action object.");
        $contextId = $actionData->context_id ?? null;



        $action = $this->actions[$actionName] ?? throw new \InvalidArgumentException("Action with name '$actionName' not found.");
        if ($action->needsContext() && $contextId === null)
            throw new \InvalidArgumentException("Action '$actionName' requires a context id.");

        $this->selectContextId($contextId);

        $result = $action->performAction($actionData, $this, $this->logger, $contextId);
        foreach ($result->context_updates as $context_update) {
            $contextStoreageDriver = $this->getContextStorageDriver();
            if ($result->switchToContextId !== null && $action->needsContext() === false)
                $this->getContextStorageDriver()->withContext($result->switchToContextId)->processContextMsg($context_update);
            else
                $this->getContextStorageDriver()->withContext($contextId)->processContextMsg($context_update);

        }

        return $result;
    }




    private static $instance = null;
    public static function getInstance() : self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

}
