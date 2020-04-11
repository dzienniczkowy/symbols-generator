<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class ExtractCommand extends Command
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /** @var ZipArchive */
    private $zip;

    /** @var string */
    private $root;

    public function __construct(string $root)
    {
        parent::__construct();
        $this->root = $root;
        $this->filesystem = new Filesystem();
        $this->zip = new ZipArchive();
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:extract')
            ->setDescription('Extract archive');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
            $this->zip->extractTo($this->root.'/tmp');
            $this->zip->close();
        }
    }
}
