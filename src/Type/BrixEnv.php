<?php

namespace Brix\Core\Type;

use Lack\Keystore\KeyStore;
use Lack\Keystore\Type\Service;
use Lack\OpenAi\LackOpenAiClient;
use Lack\OpenAi\LackOpenAiFacet;
use Lack\OpenAi\Logger\CliLogger;
use Leuffen\Brix\Api\OpenAiApi;
use Phore\FileSystem\PhoreDirectory;

class BrixEnv
{

    public function __construct(
        public KeyStore $keyStore,
        public readonly BrixConfig $brixConfig,
        public readonly PhoreDirectory $rootDir,
        public readonly string $contextCombined
    ) {

    }


    public function getOpenAiApi() : LackOpenAiClient {
        return new LackOpenAiClient($this->keyStore->getAccessKey(Service::OpenAi), new CliLogger());
    }

    public function getOpenAiQuickFacet() : LackOpenAiFacet {
        return new LackOpenAiFacet($this->getOpenAiApi());
    }

    public function getState(string $scope) : BrixState {
        return new BrixState($this->rootDir->withFileName(".brix.state.yml"), $scope);
    }

}
