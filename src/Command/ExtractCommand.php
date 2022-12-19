<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class ExtractCommand extends Command
{

    private ZipArchive $zip;

    private string $root;

    private string $tmp;

    public function __construct(string $root, string $tmp)
    {
        parent::__construct();
        $this->root = $root;
        $this->tmp = $tmp;
        $this->zip = new ZipArchive();
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:extract')
            ->setDescription('Extract archive');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write('Rozpakowywanie...');
        $this->extract();
        $output->writeln(' zakończone');

        return 0;
    }

    public function extract()
    {
        $files = glob($this->root.'/*.zip');

        if ($this->zip->open(end($files)) === true) {
            $this->zip->extractTo($this->tmp);
            $this->zip->close();
        }
    }
}
