<?php

namespace Wulkanowy;

use DOMDocument;
use SimpleXMLElement;

class AndroidXmlGenerator implements GeneratorInterface
{
    private $counties;

    public function __construct(array $counties)
    {
        $this->counties = $counties;
    }

    public function save(string $filename) : bool
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><resources/>');
        $xml->addAttribute('xmlns:xmlns:tools', 'http://schemas.android.com/tools');
        $xml->addAttribute('android:tools:ignore', 'MissingTranslation');

        $countiesKeys = $xml->addChild('string-array');
        $countiesKeys->addAttribute('name', 'counties');

        foreach ($this->counties as $name) {
            $countiesKeys->addChild('item', $name[0]);
        }

        $countiesValues = $xml->addChild('string-array');
        $countiesValues->addAttribute('name', 'counties_values');

        foreach ($this->counties as $name) {
            $countiesValues->addChild('item', $name[1]);
        }

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        $output = preg_replace_callback('/^( +)</m', function ($a) {
            return str_repeat(' ', intval(strlen($a[1]) / 2) * 4).'<';
        }, $dom->saveXML());

        return file_put_contents($filename, $output);
    }
}
