<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wulkanowy\SymbolsGenerator\Service\Filesystem;
use Wulkanowy\SymbolsGenerator\Service\OutputGeneratorService;

use function json_decode;

class GenerateCommand extends Command
{
    private string $root;

    private string $tmp;

    private OutputGeneratorService $output;

    private Filesystem $filesystem;

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
            ->addArgument('domain', InputArgument::OPTIONAL, 'Register main domain to check', 'vulcan.net.pl')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Generator output [txt|html|xml]', 'txt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getOption('output');
        $domain = $input->getArgument('domain');
        $output->write('Generowanie pliku...');
        $this->generate($type, $domain);
        $output->writeln(' zakończone');
        $output->writeln('<fg=green>Zapisano do pliku output.'.$type.'</>');

        return 0;
    }

    private function generate(string $type, $domain)
    {
        $symbols = json_decode($this->filesystem->getContents($this->tmp.'/symbols-checked.json'), true);

        switch ($type) {
            case 'txt':
                $output = $this->output->getText($symbols['working']);
                break;

            case 'html':
                $output = $this->output->getHtml($symbols, $domain);
                break;

            case 'xml':
                $output = $this->output->getAndroidXml($symbols['working']);
                break;

            default:
                echo 'Unsupported output type'.PHP_EOL;

                return;
        }

        $this->filesystem->dumpFile($this->root.'/output.'.$type, $output);
    }
}
