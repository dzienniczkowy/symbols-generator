<?php

namespace Wulkanowy\SymbolsGenerator\Service;

use DOMDocument;
use DOMImplementation;
use SimpleXMLElement;

class OutputGeneratorService
{
    public function getText(array $symbols)
    {
        $output = [];

        usort($symbols, function ($a, $b) {
            return $a[1] <=> $b[1];
        });

        foreach ($symbols as $item) {
            $output[] = $item[1];
        }

        return implode(PHP_EOL, $output);
    }

    public function getHtml(array $symbols, string $domain)
    {
        $document = (new DOMImplementation())->createDocument(
            null,
            'html',
            (new DOMImplementation())->createDocumentType('html')
        );
        $document->formatOutput = true;

        $html = $document->documentElement;
        $head = $document->createElement('head');
        $title = $document->createElement('title');
        $body = $document->createElement('body');
        $h1 = $document->createElement('h1');

        $h1->appendChild($document->createTextNode('Symbole dla domeny '.$domain));
        $title->appendChild($document->createTextNode('Symbole dla domeny '.$domain));
        $head->appendChild($title);
        $html->appendChild($head);
        $body->appendChild($h1);

        foreach ($symbols as $title => $section) {
            usort($section, function ($a, $b) {
                return $a[1] <=> $b[1];
            });

            $details = $document->createElement('details');
            $summary = $document->createElement('summary');
            $summaryText = $document->createTextNode($title.' ('.count($section).')');
            $summary->appendChild($summaryText);
            $details->appendChild($summary);

            $ul = $document->createElement('ul');
            foreach ($section as $item) {
                $link = $document->createElement('a');
                $link->setAttribute('href', 'https://uonetplus.'.$domain.'/'.$item[1]);
                $link->appendChild($document->createTextNode($item[0]));

                $li = $document->createElement('li');
                $li->appendChild($link);

                $ul->appendChild($li);
            }
            $details->appendChild($ul);
            $body->appendChild($details);
        }
        $html->appendChild($body);

        return $document->saveHTML();
    }

    public function getAndroidXml(array $symbols)
    {
        usort($symbols, function ($a, $b) {
            return $a[1] <=> $b[1];
        });

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
            return str_repeat(' ', (int) (strlen($a[1]) / 2) * 4).'<';
        }, $dom->saveXML());
    }
}
