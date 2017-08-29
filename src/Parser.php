<?php

namespace Wulkanowy;

use InvalidArgumentException;
use SimpleXMLElement;

class Parser
{
    private $filename;

    public function __construct(string $filename)
    {
        if (empty($filename)) {
            throw new InvalidArgumentException('Xml file not found');
        }

        $this->filename = $filename;
    }

    public function parse() : array
    {
        $xml = new SimpleXMLElement(file_get_contents($this->filename));

        $counties = [];

        foreach ($xml->catalog->row as $element) {
            $description = (string) $element->NAZWA_DOD;
            $name = ucfirst($element->NAZWA);

            $path = (new StringFormatter($name))
                    ->latinize()
                    ->lowercase()
                    ->removeDashes()
                    ->removeSpaces()
                    ->get();

            if ('powiat' == $description) {
                $counties['powiat'.$path] = 'Powiat '.$name;
            }

            if ('gmina miejska' == $description
                || 'gmina miejsko-wiejska' == $description
                || 'gmina wiejska' == $description) {
                $counties['gmina'.$path] = 'Gmina '.$name;
            }

            $counties[$path] = $name;
        }

        return $counties;
    }
}
