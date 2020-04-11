<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wulkanowy\SymbolsGenerator\Service\Filesystem;
use Wulkanowy\SymbolsGenerator\Service\OutputGeneratorService;
use function json_decode;

class GenerateCommand extends Command
{
    /** @var string */
    private $root;

    /** @var string */
    private $tmp;

    /** @var OutputGeneratorService */
    private $output;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(string $root, string $tmp, OutputGeneratorService $output, Filesystem $filesystem)
    {
        parent::__construct();
        $this->root = $root;
        $this->tmp = $tmp;
        $this->output = $output;
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
        $symbols = json_decode($this->filesystem->getContents($this->tmp . '/symbols-checked.json'))->working;

        $output = $this->output->getAndroidXml($symbols);

        $this->filesystem->dumpFile($this->root . '/api_symbols.xml', $output);

        return 0;
    }
}
