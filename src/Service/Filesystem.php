<?php

namespace Wulkanowy\SymbolsGenerator\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class Filesystem extends SymfonyFilesystem
{
    /**
     * Return the contents of the file at the given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function getContents($path)
    {
        if (!$this->exists($path)) {
            throw new IOException(sprintf('Unable to read file "%s". Either it does not exist or it is not readable.', $path));
        }

        return file_get_contents($path);
    }
}
