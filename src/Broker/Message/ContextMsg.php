<?php

namespace Brix\Core\Broker\Message;

class ContextMsg
{

    public function __construct(
        public string $keyId,

        public string $desc,

        public mixed $value
    ) {

    }

}
