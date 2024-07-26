<?php

namespace Brix\Core\Broker\Context;

use Brix\Core\Broker\Message\ContextMsg;
use Phore\Core\Exception\NotFoundException;
use Phore\FileSystem\PhoreFile;
use Phore\ObjectStore\ObjectStore;
use Phore\ObjectStore\Type\ObjectStoreObject;

class ObjectStoreStorageDriver
{


    public function __construct(public ObjectStore $objectStore)
    {

    }


    private $selectedContextId = null;


    public function withContext(?string $contextId) : self
    {
        $obj= new self($this->objectStore);
        $obj->selectedContextId = $contextId;
        return $obj;
    }


    public function exists() : bool {
        return $this->getStorageFile($this->selectedContextId ?? throw new \InvalidArgumentException("No contextId selected"))->exists();
    }



    private function getStorageFile($contextId) : ObjectStoreObject
    {
        return $this->objectStore->object('/context/' . $contextId . '.json');
    }

    public function getData() : array|null
    {
        if ($this->selectedContextId === null)
            return null;
        $data = $this->getStorageFile($this->selectedContextId ?? throw new \InvalidArgumentException("No ContextId selected"))->getJson();
        return $data;
    }

    public function setData(array $data) : void
    {
        $this->getStorageFile($this->selectedContextId ?? throw new \InvalidArgumentException("No contextId selected"))->putJson($data);
    }

    public function createContext(string $newContextId, string $shortInfo) : void
    {
        $this->getStorageFile($newContextId, '')->putJson(["__shortInfo" => $shortInfo, "__created" => phore_datetime()]);
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

        if (is_object($contextMsg->value))
            $contextMsg->value = (array)$contextMsg->value;

        $data[$contextMsg->keyId] = [
            "desc" => $contextMsg->desc,
            "updated" => phore_datetime(),
            "data" => $contextMsg->value,
        ];
        $this->setData($data);
    }


    protected function updateIndex($contextId, $shortInfo) {
        $index = $this->objectStore->object('/context/__index.json');
        $data = $index->getJson();
        $data[$contextId] = $shortInfo;
        $index->putJson($data);
    }


    public function getSelectedContextId() : ?string
    {
        try {

            return $this->objectStore->object('/context/__state.txt')->get();
        } catch ( NotFoundException $e) {
            return null;
        }
    }

    public function setSelectedContextId(string|null $contextId) : void
    {
        $this->objectStore->object('/context/__selected.txt')->put($contextId ?? "");
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
