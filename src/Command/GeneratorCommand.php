<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('generate')
            ->setDescription('Generate symbols list');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->find('generate:extract')->run(new ArrayInput([]), $output);
        $this->getApplication()->find('generate:parse')->run(new ArrayInput([]), $output);
        $this->getApplication()->find('generate:check')->run(new ArrayInput([]), $output);
        $this->getApplication()->find('generate:xml')->run(new ArrayInput([]), $output);
    }
}
