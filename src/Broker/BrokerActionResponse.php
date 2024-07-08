<?php

namespace Brix\Core\Broker;

use Brix\Core\Broker\Message\ContextMsg;

class BrokerActionResponse
{


    /**
     * @var string
     */
    public $type = "success";

    /**
     * @var string|null
     */
    public $message = null;

    /**
     * @var null|array
     */
    public $data = null;

    /**
     * @var array
     */
    public $context_updates = [];

    public function addContextUpdate(string $actionId, string $key, string $value, string $description) : void
    {
        $this->context_updates[] = new ContextMsg($actionId, $key, $value, $description);
    }

}
