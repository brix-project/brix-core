<?php

namespace Brix\Core;


use Brix\Core\Type\BrixEnv;

class AbstractBrixCommand
{

    protected BrixEnv $brixEnv;

    public function __construct() {
        $this->brixEnv = $brixEnv = BrixEnvFactorySingleton::getInstance()->getEnv();
    }
}
