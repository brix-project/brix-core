<?php

namespace Brix\Core\Broker;

class ActionInfoType
{

    public function __construct(
        public string $actionName,

        public string $desc,

        /**
         * @var class-string
         */
        public string $inputClassName,


        public string $inputSchema
    )
    {

    }


}
