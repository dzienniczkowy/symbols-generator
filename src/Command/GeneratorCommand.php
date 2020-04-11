<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorCommand extends Command
{
    private const BASE_URL = 'vulcan.net.pl';
    private const TIMEOUT = 25;
    private const CONCURRENCY = 25;

    protected function configure(): void
    {
        $this
            ->setName('generate')
            ->setDescription('Generate symbols list')
            ->setDefinition(new InputDefinition([
                new InputArgument('domain', InputArgument::OPTIONAL, 'Register main domain to check', self::BASE_URL),
                new InputOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Timeout', self::TIMEOUT),
                new InputOption('concurrency', null, InputOption::VALUE_OPTIONAL, 'Concurrency', self::CONCURRENCY),
                new InputOption('clean', null, InputOption::VALUE_NONE, 'Clean files before work'),
                new InputOption('output', null, InputOption::VALUE_OPTIONAL, 'Generator output [txt|html|xml]', 'txt'),
            ]));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('clean')) $this->getApplication()->find('generate:clean')->run(new ArrayInput([]), $output);
        $this->getApplication()->find('generate:extract')->run(new ArrayInput([]), $output);
        $this->getApplication()->find('generate:parse')->run(new ArrayInput([]), $output);
        $this->getApplication()->find('generate:check')->run(new ArrayInput([
            'command' => 'generate:check',
            'domain' => $input->getArgument('domain'),
            '--timeout' => $input->getOption('timeout'),
            '--concurrency' => $input->getOption('concurrency'),
        ]), $output);
        $this->getApplication()->find('generate:output')->run(new ArrayInput([
            'command' => 'generate:output',
            'domain' => $input->getArgument('domain'),
            '--output' => $input->getOption('output'),
        ]), $output);

        return 0;
    }
}
