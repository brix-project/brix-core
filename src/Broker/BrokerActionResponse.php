<?php

namespace Brix\Core\Broker;

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

}
