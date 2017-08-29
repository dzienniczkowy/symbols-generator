<?php

namespace Wulkanowy;

class SimpleListGenerator implements GeneratorInterface
{
    private $counties;

    public function __construct(array $counties)
    {
        $this->counties = $counties;
    }

    public function save(string $filename) : bool
    {
        $items = [];

        foreach ($this->counties as $name) {
            $items[] = $name[0];
        }

        return file_put_contents($filename, implode(PHP_EOL, $items));
    }
}
