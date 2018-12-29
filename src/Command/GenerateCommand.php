<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use DOMDocument;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    /** @var string */
    private $root;

    public function __construct(string $root)
    {
        parent::__construct();
        $this->root = $root;
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:xml')
            ->setDescription('Generate xml file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("Generowanie pliku...");
        $this->generate();
        $output->writeln(" zakończone");
        $output->writeln('<fg=green>Zapisano do pliku api_symbols.xml</>');
    }

    private function generate()
    {
        $counties = \json_decode(file_get_contents($this->root . '/tmp/checked-symbols.json'));

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><resources/>');
        $xml->addAttribute('xmlns:xmlns:tools', 'http://schemas.android.com/tools');
        $xml->addAttribute('android:tools:ignore', 'MissingTranslation,Typos');
        $countiesKeys = $xml->addChild('string-array');
        $countiesKeys->addAttribute('name', 'symbols');
        foreach ($counties as $name) {
            $countiesKeys->addChild('item', $name[0]);
        }
        $countiesValues = $xml->addChild('string-array');
        $countiesValues->addAttribute('name', 'symbols_values');
        foreach ($counties as $name) {
            $countiesValues->addChild('item', $name[1]);
        }
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $output = preg_replace_callback('/^( +)</m', function ($a) {
            return str_repeat(' ', (int)(\strlen($a[1]) / 2) * 4) . '<';
        }, $dom->saveXML());

        return file_put_contents($this->root . '/api_symbols.xml', $output);
    }
}
