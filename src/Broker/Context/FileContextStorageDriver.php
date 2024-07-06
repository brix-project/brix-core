<?php

namespace Brix\Core\Broker\Context;

use Phore\FileSystem\PhoreFile;

class FileContextStorageDriver
{


    public function __construct(public string $contextPath)
    {
        phore_dir($this->contextPath)->mkdir("0755");
    }


    private $selectedContextId = null;
    private $selectedActionId = null;


    public function selectContext(string $contextId) : string
    {
        $this->selectedContextId = $contextId;
    }

    public function selectAction(string $actionId) : string
    {
        $this->selectedActionId = $actionId;
    }


    private function getStorageFile($contextId) : PhoreFile
    {
        return phore_file($this->contextPath . '/' . $contextId . '.yml');
    }

    public function getData(string $actionId = null) : array|string|int|null
    {
        $data = $this->getStorageFile($this->selectedContextId)->get_yaml();
        if ($actionId === null)
            return $data;
        return $data[$actionId] ?? null;
    }

    public function setData(string $actionId, $value) : void
    {
        $data = $this->getStorageFile($this->selectedContextId)->get_yaml();
        $data[$actionId] = $value;
        $this->getStorageFile($this->selectedContextId)->set_yaml($data);
    }

    public function createContext(string $newContextId, string $shortInfo) : void
    {
        $this->getStorageFile($newContextId, '')->set_yaml(["__shortInfo" => $shortInfo, "__created" => date("Y-m-d H:i:s")]);
    }


    public function getShortInfo()
    {
        return $this->getData("__shortInfo");

    }

    public function setShortInfo( string $shortInfo)
    {
        $this->setData("__shortInfo", $shortInfo);
    }

    public function listContexts(string $filter = null) : array
    {
        $ret = [];
        foreach (phore_dir($this->contextPath)->listFiles() as $file) {
            if ( ! $file->isFile())
                continue;
            if ($file->getExtension() !== "yml")
                continue;
            $contextId = $file->getFilename();
            $data = $file->get_yaml();
            $shortInfo = $data["__shortInfo"] ?? throw new \InvalidArgumentException("Invalid context file: $contextId");

            if ($filter !== null) {
                if ( ! str_contains($shortInfo, $filter) && ! str_contains($contextId, $filter))
                    continue;
            }

            $ret[] = ["contextId" => $contextId, "shortInfo" => $shortInfo, "created" => $data["__created"]];
        }
        return $ret;
    }


}
