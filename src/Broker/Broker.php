<?php

namespace Brix\Core\Broker;
use Brix\Core\BrixEnvFactorySingleton;
use Brix\Core\Broker\Context\FileContextStorageDriver;
use Brix\Core\Type\BrixEnv;

class Broker
{

    public readonly BrixEnv $brixEnv;


    public readonly FileContextStorageDriver $contextStorageDriver;

    private function __construct() {
        $this->brixEnv = BrixEnvFactorySingleton::getInstance()->getEnv();
        $this->contextStorageDriver = new FileContextStorageDriver($this->brixEnv->rootDir . "/.context");
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

            $ret[] = new ActionInfoType($actionName, $action->getDescription(), $action->getInputClass());
        }
        return $ret;
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
