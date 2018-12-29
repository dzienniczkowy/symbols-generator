<?php

namespace Wulkanowy\SymbolsGenerator\Command;


use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    /** @var string */
    private $root;

    private $client;

    public function __construct(string $root)
    {
        parent::__construct();
        $this->root = $root;
        $this->client = new Client(['base_uri' => 'https://uonetplus.vulcan.net.pl/']);
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:check')
            ->setDescription('Check symbols');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('Testowanie...');
        $unchecked = json_decode(file_get_contents($this->root . '/tmp/unchecked-symbols.json'), true);
        $start = microtime(true);
        $amount = $this->check($unchecked, $output);
        $output->writeln('Testowanie zakończone w ' . round(microtime(true) - $start, 2) . ' sekund(y).');
        $output->writeln('Odnaleziono ' . $amount . ' z ' . count($unchecked));
    }

    private function check(array $symbols, OutputInterface $o): int
    {
        $filtered = [];
        $requests = function ($items) {
            foreach ($items as $key => $value) {
                yield new Request('GET', $key);
            }
        };

        $amount = count($symbols);
        $o->write(PHP_EOL);
        $pool = new Pool($this->client, $requests($symbols), [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($o, $symbols, $amount, &$filtered) {
                $path = array_keys($symbols)[$index];
                $value = [$symbols[$path], $path];
                $o->write('[' . ($index + 1) . '/' . $amount . '] ' . $path . ' – ');
                if (strpos($response->getBody(), 'Podany identyfikator klienta jest niepoprawny') !== false) {
                    $o->writeln('<fg=red>Nie udało się, bo brak dziennika</>');
                } elseif (strpos($response->getBody(), 'Zakończono świadczenie usługi dostępu do aplikacji') !== false) {
                    $o->writeln('<fg=red>Nie udało się, bo zakończono świadczenie usługi dostępu do aplikacji</>');
                } elseif (strpos($response->getBody(), 'Przerwa techniczna') !== false) {
                    $o->writeln('<fg=yellow>Przerwa techniczna</>');
                    $filtered[$index] = $value;
                } elseif (strpos($response->getBody(), 'Trwa aktualizacja bazy danych') !== false) {
                    $o->writeln('<fg=yellow>Udało się, ale trwa aktualizacja bazy danych</>');
                    $filtered[$index] = $value;
                } else {
                    $o->writeln('<fg=green>Udało się!</>');
                    $filtered[$index] = $value;
                }
            },
            'rejected' => function () use ($o) {
                $o->writeln('<error>Error Processing Request</error>');
            },
        ]);
        $pool->promise()->wait();
        usort($filtered, function ($a, $b) {
            return $a[1] <=> $b[1];
        });

        file_put_contents($this->root . '/tmp/checked-symbols.json', json_encode($filtered, JSON_PRETTY_PRINT));

        return count($filtered);
    }
}
