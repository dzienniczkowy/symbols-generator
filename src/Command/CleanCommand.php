<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class CleanCommand extends Command
{
    protected static $defaultName = 'generate:clean';

    /** @var string */
    private $tmp;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(string $tmp, Filesystem $filesystem)
    {
        parent::__construct();
        $this->tmp = $tmp;
        $this->filesystem = $filesystem;
    }

    protected function configure()
    {
        $this->setDescription('Clean already generated files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write('Czyszczenie... ');
        $this->filesystem->remove($this->tmp);
        $output->writeln(' zakończone.');

        return 0;
    }
}
