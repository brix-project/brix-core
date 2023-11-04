<?php

namespace Brix\Core\Type;

use Phore\FileSystem\PhoreFile;

class BrixState
{

    public function __construct(private PhoreFile $file, private string $scope) {

    }


    private function loadData() {
        if ( ! $this->file->exists())
            return [];
        return $this->file->get_yaml();
    }

    private function saveData(array $data) {
        $this->file->set_yaml($data);
    }


    public function get(string $key) : mixed
    {
        $data = $this->loadData();
        if ($data[$this->scope][$key] ?? null === null)
            return null;
        return $data[$this->scope][$key];
    }

    public function set(string $key, $data) : void {
        $data = $this->loadData();
        if ( ! isset ($data[$this->scope]))
            $data[$this->scope] = [];
        $data[$this->scope][$key] = $data;
        $this->saveData($data);
    }


    public function getNumber(string $key, int $default=1) : int
    {
        $data = $this->loadData();
        if ( ! isset($data[$this->scope][$key]))
            return $default;
        return intval($data[$this->scope][$key]);
    }

    public function increment(string $key, int $by = 1) : int
    {
        $data = $this->loadData();
        if ( ! isset ($data[$this->scope]))
            $data[$this->scope] = [];
        if ( ! isset($data[$this->scope][$key]))
            $data[$this->scope][$key] = 0;
        $data[$this->scope][$key] += $by;
        $this->saveData($data);
        return $data[$this->scope][$key];
    }

}
