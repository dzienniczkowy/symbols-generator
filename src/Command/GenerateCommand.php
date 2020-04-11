<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use DOMDocument;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wulkanowy\SymbolsGenerator\Service\Filesystem;
use function json_decode;
use function strlen;

class GenerateCommand extends Command
{
    /** @var string */
    private $root;

    /** @var string */
    private $tmp;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(string $root, string $tmp, Filesystem $filesystem)
    {
        parent::__construct();
        $this->root = $root;
        $this->tmp = $tmp;
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:xml')
            ->setDescription('Generate xml file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('Generowanie pliku...');
        $this->generate();
        $output->writeln(' zakończone');
        $output->writeln('<fg=green>Zapisano do pliku api_symbols.xml</>');

        return 0;
    }

    private function generate()
    {
        $counties = json_decode($this->filesystem->getContents($this->tmp.'/checked-symbols-working.json'));

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
            return str_repeat(' ', (int) (strlen($a[1]) / 2) * 4).'<';
        }, $dom->saveXML());

        $this->filesystem->dumpFile($this->root.'/api_symbols.xml', $output);

        return 0;
    }
}
