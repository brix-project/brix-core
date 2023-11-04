<?php

namespace Brix\Core;

use Brix\Core\Type\BrixConfig;
use Brix\Core\Type\BrixEnv;
use Lack\Keystore\KeyStore;


class BrixEnvFactorySingleton
{

    public static function getInstance() : BrixEnvFactorySingleton
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }


    public function getEnv() : BrixEnv
    {
        $curDir = phore_dir(getcwd());
        $brixConfig = null;
        while (true) {
            $brixFile = $curDir->withFileName(".brix.yml");
            if ($brixFile->exists()) {
                if ($brixFile->get_contents() === "")
                    $brixFile->set_contents("version: '1.0'");
                $brixConfig = new BrixConfig($brixFile);
                break;
            }
            $curDir = $curDir->withParentDir();
            if ((string)$curDir === "/")
                throw new \InvalidArgumentException("Cannot find .brix.yml in current or parent directories.");
        }
        /* @var $brixConfig BrixConfig */
        $rootDir = $curDir;

        $contextCombined = $brixConfig->context ?? "";
        if (isset ($brixConfig->context_file)) {
            $contextCombined .= "\n" . phore_file($brixConfig->context_file)->get_contents();
        }

        return new BrixEnv(
            KeyStore::Get(),
            $brixConfig,
            $curDir,
            $contextCombined
        );
    }


}
