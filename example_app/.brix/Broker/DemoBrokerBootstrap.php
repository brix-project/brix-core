<?php

declare(strict_types=1);

use Brix\Core\Broker\Broker;
use Brix\Core\Broker\Context\ObjectStoreStorageDriver;
use Phore\ObjectStore\Driver\FileSystemObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;

final class DemoBrokerBootstrap
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        $broker = Broker::getInstance();
        self::configureLocalContextStore($broker);
        $broker->registerAction(new CreateProjectNoteAction());

        self::$booted = true;
    }

    public static function broker(): Broker
    {
        self::boot();
        return Broker::getInstance();
    }

    private static function configureLocalContextStore(Broker $broker): void
    {
        $storeRoot = __DIR__ . '/../../runtime/objectstore';
        if (!is_dir($storeRoot)) {
            mkdir($storeRoot, 0777, true);
        }

        $contextDir = $storeRoot . '/context';
        if (!is_dir($contextDir)) {
            mkdir($contextDir, 0777, true);
        }
        if (!file_exists($contextDir . '/__index.json')) {
            file_put_contents($contextDir . '/__index.json', '{}');
        }
        if (!file_exists($contextDir . '/__state.json')) {
            file_put_contents($contextDir . '/__state.json', '{"selectedContextId":null}');
        }

        $reflection = new ReflectionClass($broker);
        $property = $reflection->getProperty('contextStorageDriver');
        $property->setAccessible(true);
        $property->setValue(
            $broker,
            new ObjectStoreStorageDriver(new ObjectStore(new FileSystemObjectStoreDriver($storeRoot)))
        );
    }
}
