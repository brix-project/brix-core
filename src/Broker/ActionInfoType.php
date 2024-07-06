<?php

namespace Brix\Core\Broker;

class ActionInfoType
{

    public function __construct(
        public string $actionName,

        public string $desc,

        public string $inputSchema
    )
    {

    }


}
