<?php

namespace Wulkanowy;

use DOMDocument;
use SimpleXMLElement;

class CountiesGenerator
{
    private $counties;

    public function __construct(array $counties)
    {
        $this->counties = $counties;
    }

    public function saveAsXml(string $filename) : bool
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><resources/>');
        $xml->addAttribute('xmlns:xmlns:tools', 'http://schemas.android.com/tools');
        $xml->addAttribute('android:tools:ignore', 'MissingTranslation');

        $countiesKeys = $xml->addChild('string-array');
        $countiesKeys->addAttribute('name', 'counties');

        foreach ($this->counties as $name) {
            $countiesKeys->addChild('item', $name[1]);
        }

        $countiesValues = $xml->addChild('string-array');
        $countiesValues->addAttribute('name', 'counties_values');

        foreach ($this->counties as $name) {
            $name = (new StringFormatter($name[1]))
                    ->latinize()
                    ->lowercase()
                    ->removeDashes()
                    ->removeSpaces()
                    ->get();
            $countiesValues->addChild('item', $name);
        }

        $dom = new DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return file_put_contents($filename, $dom->saveXML());
    }
}
