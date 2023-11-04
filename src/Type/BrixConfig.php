<?php

namespace Brix\Core\Type;

class BrixConfig
{

    public function __construct(
        public string $fileNanme,
        public array|null $data = null
    ) {
        if ($this->data === null)
            $this->data = phore_file($fileNanme)->get_yaml();
    }

    /**
     * Load the config section from the config file
     *
     * @template T
     * @param string $key
     * @param class-string<T> $cast
     * @return T
     */
    public function get(string $key, string $cast, string $template = null) : mixed
    {
        if ( ! isset ($this->data[$key])) {
            if ($template !== null) {
                $this->initFromTemplate($key, $template);
                $this->data = phore_file($this->fileNanme)->get_yaml();
                if ( ! isset ($this->data[$key]))
                    throw new \InvalidArgumentException("Cannot find key '$key' in config file '$this->fileNanme'");
            } else {
                throw new \InvalidArgumentException("Cannot find key '$key' in config file '$this->fileNanme'");
            }
        }
        return phore_hydrate($this->data[$key], $cast);
    }


    public function has(string $key) : bool
    {
        return isset ($this->data[$key]);
    }

    /**
     * Check weather the config file has a key or apply the tamplate to the file
     *
     * @param $key
     * @param string $template
     * @return void
     * @throws \Exception
     */
    public function initFromTemplate($key, string $template) {
        if ( ! $this->has($key)) {
            phore_file($this->fileNanme)->append_content("\n" . $template);
        }
    }

}
