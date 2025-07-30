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
        try {
            $data = $this->getStorageFile($this->selectedContextId)->getJson();
            return $data;
        } catch (NotFoundException $e) {
            throw new \InvalidArgumentException("Context '$this->selectedContextId' not found.");
        }

    }


    public function getValue($key) : mixed
    {
        $data = $this->getData();
        if (isset($data[$key]))
            return $data[$key]["data"];
        return null;
    }

    public function setData(array $data) : void
    {
        $this->getStorageFile($this->selectedContextId ?? throw new \InvalidArgumentException("No contextId selected"))->putJson($data);
        $this->updateIndex($this->selectedContextId, $data["__shortInfo"] ?? "", $data["__created"] ?? phore_datetime());
    }

    public function createContext(string $newContextId, string $shortInfo) : void
    {
        $this->getStorageFile($newContextId, '')->putJson(["__shortInfo" => $shortInfo, "__created" => phore_datetime()]);
        $this->updateIndex($newContextId, $shortInfo, phore_datetime());
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


    protected function updateIndex($contextId, $shortInfo, $created) {
        $index = $this->objectStore->object('/context/__index.json');
        $data = $index->getJson();
        $data[$contextId] = ["shortInfo" => $shortInfo, "created" => $created];
        $index->putJson($data, true);
    }


    public function getSelectedContextId() : ?string
    {
        try {

            return $this->objectStore->object('/context/__state.json')->getJson()["selectedContextId"];
        } catch ( NotFoundException $e) {
            return null;
        }
    }

    public function setSelectedContextId(string|null $contextId) : void
    {
        $this->objectStore->object('/context/__state.json')->putJson(["selectedContextId" => $contextId]);
    }

    public function rmContext(string $contextId) : void
    {
        $this->objectStore->object('/context/' . $contextId . '.json')->remove();
        $index = $this->objectStore->object('/context/__index.json')->getJson();
        unset($index[$contextId]);
        $this->objectStore->object('/context/__index.json')->putJson($index, true);
    }

    public function listContexts(string $filter = null) : array
    {
        $ret = [];
        try {
            $this->objectStore->object('/context/__index.json')->getJson();
        } catch (NotFoundException $e) {
            $this->objectStore->object('/context/__index.json')->putJson([]);
        }
        $index = $this->objectStore->object('/context/__index.json')->getJson();
        foreach ($index as $contextId => $info) {
            if ($filter !== null) {
                if ( ! str_contains(strtolower($info["shortInfo"]), strtolower($filter)) && ! str_contains(strtolower($contextId), strtolower($filter)))
                    continue;
            }

            $ret[] = ["contextId" => $contextId, "shortInfo" => $info["shortInfo"], "created" => $info["created"] ?? ""];
        }
        return $ret;
    }


}
