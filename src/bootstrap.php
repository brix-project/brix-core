<?php

namespace Brix\Core;

use Phore\Cli\CliDispatcher;

CliDispatcher::addClass(Actions::class);

try {
    BrixEnvFactorySingleton::getInstance()->getEnv();
} catch (\Throwable $e) {
    // Ignore: allows using brix outside a project, while still preloading brix-autoload.php inside a project.
}


