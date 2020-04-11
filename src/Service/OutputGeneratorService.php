<?php

namespace Wulkanowy\SymbolsGenerator\Service;

use DOMDocument;
use SimpleXMLElement;

class OutputGeneratorService
{
    function getAndroidXml(array $symbols)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><resources/>');
        $xml->addAttribute('xmlns:xmlns:tools', 'http://schemas.android.com/tools');
        $xml->addAttribute('android:tools:ignore', 'MissingTranslation,Typos');
        $symbolsKeys = $xml->addChild('string-array');
        $symbolsKeys->addAttribute('name', 'symbols');
        foreach ($symbols as $name) {
            $symbolsKeys->addChild('item', $name[0]);
        }
        $symbolsValues = $xml->addChild('string-array');
        $symbolsValues->addAttribute('name', 'symbols_values');
        foreach ($symbols as $name) {
            $symbolsValues->addChild('item', $name[1]);
        }
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return preg_replace_callback('/^( +)</m', function ($a) {
            return str_repeat(' ', (int)(strlen($a[1]) / 2) * 4) . '<';
        }, $dom->saveXML());
    }
}
