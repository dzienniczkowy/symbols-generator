<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wulkanowy\SymbolsGenerator\Service\StringFormatterService;

class ParseCommand extends Command
{
    /** @var string */
    private $tmp;

    /** @var StringFormatterService */
    private $formatter;

    public function __construct(string $tmp, StringFormatterService $formatter)
    {
        parent::__construct();
        $this->tmp = $tmp;
        $this->formatter = $formatter;
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:parse')
            ->setDescription('Parse list');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

        $xml = new SimpleXMLElement(file_get_contents(end($files)));
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

        file_put_contents($this->tmp.'/unchecked-symbols.json', json_encode($symbols, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return count($symbols);
    }
}
