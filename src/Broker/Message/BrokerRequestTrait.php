<?php

namespace Brix\Core\Broker\Message;

trait BrokerRequestTrait
{

    /**
     * The Action to call. Keep null - will be set by the broker
     *
     * @var string
     */
    public ?string $action_name;

    /**
     * The context id to use. Keep null - will be set by the broker
     *
     * @var string|null
     */
    public ?string $context_id;

}
