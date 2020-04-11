<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class CheckCommand extends Command
{
    private const BASE_URL = 'https://uonetplus.vulcan.net.pl/';

    /** @var string */
    private $root;

    /** @var Filesystem */
    private $filesystem;

    private $client;

    public function __construct(string $root, Filesystem $filesystem)
    {
        parent::__construct();
        $this->root = $root;
        $this->filesystem = $filesystem;
        $this->client = new Client(['base_uri' => self::BASE_URL]);
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
        $results = $this->check($unchecked, $output);
        $totalTime = round(microtime(true) - $start, 2);

        $output->writeln("Testowanie... zakończone.");

        $this->saveResults($results, 'working');
        $this->saveResults($results, 'invalid');
        $this->saveResults($results, 'end');
        $this->saveResults($results, 'exception');
        $this->saveResults($results, 'db');
        $this->saveResults($results, 'break');
        $this->saveResults($results, 'unknown');

        $this->showSummary($output, $unchecked, $results, $totalTime);

        return 0;
    }

    private function check(array $symbols, OutputInterface $o): array
    {
        $results = [
            'working' => [],
            'invalid' => [],
            'end' => [],
            'exception' => [],
            'db' => [],
            'break' => [],
            'unknown' => []
        ];
        $requests = function ($items) {
            foreach ($items as $key => $value) {
                yield new Request('GET', $key);
            }
        };

        $amount = count($symbols);
        $o->write(PHP_EOL);
        $s = $o->section();
        $pool = new Pool($this->client, $requests($symbols), [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($s, $symbols, $amount, &$results) {
                [$value, $prefix] = $this->getPrefixWithSymbol($amount, $symbols, $index);

                if (strpos($response->getBody(), 'Podany identyfikator klienta jest niepoprawny') !== false) {
                    $s->overwrite($prefix . '<fg=red>Nie udało się, bo brak dziennika</>');
                    $results['invalid'][$index] = $value;
                } elseif (strpos($response->getBody(), 'Zakończono świadczenie usługi dostępu do aplikacji') !== false) {
                    $s->overwrite($prefix . '<fg=red>Nie udało się, bo zakończono świadczenie usługi dostępu do aplikacji</>');
                    $results['end'][$index] = $value;
                } elseif (strpos($response->getBody(), 'Wystąpił nieoczekiwany wyjątek') !== false) {
                    $s->overwrite($prefix . '<fg=red>Nie udało się, bo wystąpił nieoczekiwany wyjątek</>');
                    $results['exception'][$index] = $value;
                } elseif (strpos($response->getBody(), 'Przerwa techniczna') !== false) {
                    $s->overwrite($prefix . '<fg=yellow>Przerwa techniczna</>');
                    $results['working'][$index] = $value;
                    $results['break'][$index] = $value;
                } elseif (strpos($response->getBody(), 'Trwa aktualizacja bazy danych') !== false) {
                    $s->overwrite($prefix . '<fg=yellow>Udało się, ale trwa aktualizacja bazy danych</>');
                    $results['working'][$index] = $value;
                    $results['db'][$index] = $value;
                } else {
                    $s->overwrite($prefix . '<fg=green>Udało się!</>');
                    $results['working'][$index] = $value;
                }
            },
            'rejected' => function ($reason, $index) use ($s, $symbols, $amount, &$results) {
                [$value, $prefix] = $this->getPrefixWithSymbol($amount, $symbols, $index);

                $s->overwrite($prefix . '<error>Error Processing Request: '.$reason.'</error>');
                $results['unknown'][$index] = $value;
            },
        ]);
        $pool->promise()->wait();
        $s->clear(2);

        return $results;
    }

    private function getPrefixWithSymbol(int $amount, array $symbols, int $index): array {
        $path = array_keys($symbols)[$index];

        return [
            [$symbols[$path], $path],
            '[' . ($index + 1) . '/' . $amount . '] ' . $path . ' – '
        ];
    }

    private function saveResults(array $results, string $type): void
    {
        if (empty($results[$type])) return;

        usort($results[$type], function ($a, $b) {
            return $a[1] <=> $b[1];
        });

        $this->filesystem->dumpFile(
            $this->root . '/tmp/checked-symbols-' . $type . '.json',
            json_encode($results[$type], JSON_PRETTY_PRINT)
        );
    }

    private function showSummary(OutputInterface $output, array $unchecked, array $results, int $totalTime)
    {
        $table = new Table($output->section());
        $table->setHeaderTitle("Podsumowanie sprawdzania symboli");
        $table->setHeaders(['Całkowity czas testowania', $totalTime . ' sec.']);
        $table->addRow(['Wszystkie symbole', count($unchecked)]);
        $table->addRow(['Odnalezione symbole', count($results['working'])]);
        $table->addRow(['Błędne symbole', count($results['invalid'])]);
        $table->addRow(['Zakończono dostęp do aplikacji', count($results['end'])]);
        $table->addRow(['Nieoczekiwany wyjątek', count($results['exception'])]);
        $table->addRow(['Przerwa techniczna', count($results['break'])]);
        $table->addRow(['Aktualizacja bazy danych', count($results['db'])]);
        $table->addRow(['Nieznane', count($results['unknown'])]);
        $table->render();
    }
}
