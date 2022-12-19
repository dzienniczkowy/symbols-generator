<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Exception;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wulkanowy\SymbolsGenerator\Service\Filesystem;
use Wulkanowy\SymbolsGenerator\Service\StringFormatterService;

class ParseCommand extends Command
{
    private string $tmp;

    private StringFormatterService $formatter;

    private Filesystem $filesystem;

    public function __construct(string $tmp, StringFormatterService $formatter, Filesystem $filesystem)
    {
        parent::__construct();
        $this->tmp = $tmp;
        $this->formatter = $formatter;
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:parse')
            ->setDescription('Parse list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write('Parsowanie...');
        $amount = $this->parse();
        $output->writeln(' zakończone');
        $output->writeln('Utworzono listę '.$amount.' elementów.');

        return 0;
    }

    private function parse(): int
    {
        $files = glob($this->tmp.'/*.xml');

        try {
            $xml = new SimpleXMLElement($this->filesystem->getContents(end($files)));
        } catch (Exception $e) {
            exit($e);
        }
        $symbols = [];
        foreach ($xml->catalog->row as $element) {
            $description = (string) $element->NAZWA_DOD;
            $name = mb_strtolower($element->NAZWA);
            $path = $this->formatter->set($name)
                ->latinize()
                ->lowercase()
                ->removeDashes()
                ->removeBrackets()
                ->removeSpaces()
                ->get();
            if ('powiat' === $description) {
                $symbols['powiat'.$path] = 'Powiat '.$name;
            }
            if ('gmina miejska' === $description
                || 'gmina miejsko-wiejska' === $description
                || 'gmina wiejska' === $description) {
                $symbols['gmina'.$path] = 'Gmina '.$this->formatter->set($name)->upper()->get();
            }
            $symbols[$path] = $this->formatter->set($name)->upper()->get();
        }

        $this->filesystem->dumpFile(
            $this->tmp.'/symbols-unchecked.json',
            json_encode($symbols, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return count($symbols);
    }
}
