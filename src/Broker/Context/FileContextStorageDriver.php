<?php

namespace Brix\Core\Broker\Context;

use Brix\Core\Broker\Message\ContextMsg;
use Phore\FileSystem\PhoreFile;

class FileContextStorageDriver
{


    public function __construct(public string $contextPath)
    {
        phore_dir($this->contextPath)->mkdir(0755);
    }


    private $selectedContextId = null;


    public function withContext(?string $contextId) : self
    {
        $obj= new self($this->contextPath);
        $obj->selectedContextId = $contextId;
        return $obj;
    }


    public function exists() : bool {
        return $this->getStorageFile($this->selectedContextId ?? throw new \InvalidArgumentException("No contextId selected"))->exists();
    }



    private function getStorageFile($contextId) : PhoreFile
    {
        return phore_file($this->contextPath . '/' . $contextId . '.yml');
    }

    public function getData() : array|null
    {
        if ($this->selectedContextId === null)
            return null;
        $data = $this->getStorageFile($this->selectedContextId ?? throw new \InvalidArgumentException("No ContextId selected"))->get_yaml();
        return $data;
    }

    public function setData(array $data) : void
    {
        $this->getStorageFile($this->selectedContextId ?? throw new \InvalidArgumentException("No contextId selected"))->set_yaml($data);
    }

    public function createContext(string $newContextId, string $shortInfo) : void
    {
        $this->getStorageFile($newContextId, '')->set_yaml(["__shortInfo" => $shortInfo, "__created" => date("Y-m-d H:i:s")]);
    }


    public function getShortInfo()
    {
        return $this->getData()["__shortInfo"] ?? "";

    }

    public function setShortInfo( string $shortInfo)
    {
        $data = $this->getData();
        $data["__shortInfo"] = $shortInfo;
        $this->setData($data);
    }


    public function processContextMsg(ContextMsg $contextMsg) {
        $data = $this->getData();

        $data[$contextMsg->keyId] = [
            "desc" => $contextMsg->desc,
            "updated" => phore_datetime(),
            "data" => (array)$contextMsg->value,
        ];
        $this->setData($data);
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
