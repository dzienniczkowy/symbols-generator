<?php

namespace Wulkanowy;

use InvalidArgumentException;
use SimpleXMLElement;

class Parser
{
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
            $name = (string) $element->NAZWA;

            if ('powiat' == $description) {
                $counties[] = [$description, ucfirst('powiat '.$name)];
            } elseif ('miasto na prawach powiatu' == $description) {
                $counties[] = [$description, $name];
            }
        }

        usort($counties, function ($a, $b) {
            return $a[1] <=> $b[1];
        });

        return $counties;
    }
}
