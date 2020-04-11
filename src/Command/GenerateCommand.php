<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setName('generate:output')
            ->setDescription('Generate output to file')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Generator output [xml|txt]', 'txt');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('output');
        $output->write('Generowanie pliku...');
        $this->generate($type);
        $output->writeln(' zakończone');
        $output->writeln('<fg=green>Zapisano do pliku output.' . $type . '</>');

        return 0;
    }

    private function generate(string $type)
    {
        $symbols = json_decode($this->filesystem->getContents($this->tmp . '/symbols-checked.json'))->working;

        switch ($type) {
            case 'txt':
                $output = $this->output->getText($symbols);
                break;

            case 'xml':
                $output = $this->output->getAndroidXml($symbols);
                break;

            default:
                echo 'Unsupported output type' . PHP_EOL;
                return;
        }

        $this->filesystem->dumpFile($this->root . '/output.' . $type, $output);
    }
}
