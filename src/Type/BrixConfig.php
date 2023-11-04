<?php

namespace Brix\Core\Type;

class BrixConfig
{

    public function __construct(
        public array $data
    ) {}

    /**
     * Load the config section from the config file
     *
     * @template T
     * @param string $key
     * @param class-string<T> $cast
     * @return T
     */
    public function get(string $key, string $cast) : mixed
    {
        if ( ! isset ($this->data[$key]))
            return null;
        return phore_hydrate($this->data[$key], $cast);
    }

}
