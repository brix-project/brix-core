<?php

namespace Brix\Core\Broker;

use Brix\Core\Broker\Message\ContextMsg;

class BrokerActionResponse
{


    public function __construct(    
        /**
         * @var string
         */
            public $type = "success",
    
        /**
         * @var string|null
         */
        public $message = null,
        array|ContextMsg $context_updates = []
    )
    {
        if ( ! is_array($context_updates))
            $context_updates = [$context_updates];
        $this->context_updates = $context_updates;
    }
    


    /**
     * @var null|array
     */
    public $data = null;

    /**
     * @var ContextMsg[]
     */
    public $context_updates = [];

    public function addContextUpdate(string $key, string $description, mixed $value) : void
    {
        $this->context_updates[] = new ContextMsg($key, $description, $value);
    }

}
