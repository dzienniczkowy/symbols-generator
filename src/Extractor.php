<?php

namespace Wulkanowy\SymbolsGenerator;

use InvalidArgumentException;
use ZipArchive;

class Extractor
{
    private $filename;

    public function __construct(string $filename)
    {
        if (empty($filename)) {
            throw new InvalidArgumentException('Zip file not found');
        }

        $this->filename = $filename;
    }

    public function extract(string $pathTo) : bool
    {
        $zip = new ZipArchive();

        if ($zip->open($this->filename) === true) {
            $zip->extractTo($pathTo);
            $zip->close();

            return true;
        }

        return false;
    }
}
