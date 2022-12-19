<?php

namespace Wulkanowy\SymbolsGenerator\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Wulkanowy\SymbolsGenerator\Service\Filesystem;

class CheckCommand extends Command
{
    private const BASE_URL = 'vulcan.net.pl';
    private const TIMEOUT = 25;
    private const CONCURRENCY = 25;

    private string $tmp;

    private Filesystem $filesystem;

    private Client $client;

    private int $timeout;

    private int $concurrency;

    public function __construct(string $tmp, Filesystem $filesystem)
    {
        parent::__construct();
        $this->tmp = $tmp;
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:check')
            ->setDescription('Check symbols')
            ->addArgument('domain', InputArgument::OPTIONAL, 'Register main domain to check', self::BASE_URL)
            ->addOption('timeout', null, InputArgument::OPTIONAL, 'Timeout', self::TIMEOUT)
            ->addOption('concurrency', null, InputArgument::OPTIONAL, 'Timeout', self::CONCURRENCY);
    }

    protected function execute(InputInterface $input, OutputInterface|ConsoleOutputInterface $output): int
    {
        $domain = $input->getArgument('domain');
        $this->timeout = $input->getOption('timeout');
        $this->concurrency = $input->getOption('concurrency');
        $this->client = new Client([
            'base_uri'        => 'https://uonetplus.'.$domain.'/',
            'allow_redirects' => ['track_redirects' => true],
        ]);

        $output->write('Testowanie...');
        $unchecked = json_decode($this->filesystem->getContents($this->tmp.'/symbols-unchecked.json'), true);

        $start = microtime(true);
        $results = $this->check($unchecked, $output);
        $totalTime = round(microtime(true) - $start, 2);

        $output->writeln('Testowanie... zakończone.');

        $this->saveResults($results);
        $this->showSummary($domain, $output, $unchecked, $results, $totalTime);

        return 0;
    }

    private function check(array $symbols, ConsoleOutputInterface $o): array
    {
        $results = [
            'working'   => [],
            'adfslight' => [],
            'invalid'   => [],
            'end'       => [],
            'exception' => [],
            'db'        => [],
            'break'     => [],
            'unknown'   => [],
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
            'concurrency' => $this->concurrency,
            'options'     => ['timeout' => $this->timeout],
            'fulfilled'   => function (ResponseInterface $response, $index) use ($s, $symbols, $amount, &$results) {
                $effectiveUrl = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);
                [$value, $prefix] = $this->getPrefixWithSymbol($amount, $symbols, $index);

                if (str_contains($response->getBody(), 'Podany identyfikator klienta jest niepoprawny')) {
                    $s->overwrite($prefix.'<fg=red>Nie udało się, bo brak dziennika</>');
                    $results['invalid'][] = $value;
                } elseif (str_contains($response->getBody(), 'Zakończono świadczenie usługi dostępu do aplikacji')) {
                    $s->overwrite($prefix.'<fg=red>Nie udało się, bo zakończono świadczenie usługi dostępu do aplikacji</>');
                    $results['end'][] = $value;
                } elseif (str_contains($response->getBody(), 'Wystąpił nieoczekiwany wyjątek')) {
                    $s->overwrite($prefix.'<fg=red>Nie udało się, bo wystąpił nieoczekiwany wyjątek</>');
                    $results['exception'][] = $value;
                } elseif (str_contains($response->getBody(), 'Przerwa techniczna')) {
                    $s->overwrite($prefix.'<fg=yellow>Przerwa techniczna</>');
                    $results['working'][] = $value;
                    $results['break'][] = $value;
                } elseif (str_contains($response->getBody(), 'Trwa aktualizacja bazy danych')) {
                    $s->overwrite($prefix.'<fg=yellow>Udało się, ale trwa aktualizacja bazy danych</>');
                    $results['working'][] = $value;
                    $results['db'][] = $value;
                } else {
                    if (str_contains(end($effectiveUrl), 'adfslight')) {
                        $results['adfslight'][] = $value;
                    }
                    $s->overwrite($prefix.'<fg=green>Udało się!</>');
                    $results['working'][] = $value;
                }
            },
            'rejected' => function (Throwable $reason, $index) use ($s, $symbols, $amount, &$results) {
                [$value, $prefix] = $this->getPrefixWithSymbol($amount, $symbols, $index);

                $s->overwrite($prefix.'<error>Error Processing Request: '.$reason->getMessage().'</error>');
                $results['unknown'][] = $value;
            },
        ]);
        $pool->promise()->wait();
        $s->clear(2);

        return $results;
    }

    private function getPrefixWithSymbol(int $amount, array $symbols, int $index): array
    {
        $path = array_keys($symbols)[$index];

        return [
            [$symbols[$path], $path],
            '['.($index + 1).'/'.$amount.'] '.$path.' – ',
        ];
    }

    private function saveResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $this->filesystem->dumpFile(
            $this->tmp.'/symbols-checked.json',
            json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function showSummary(string $domain, ConsoleOutputInterface $output, array $unchecked, array $results, float $totalTime)
    {
        $table = new Table($output->section());
        $table->setHeaderTitle('Podsumowanie dla `'.$domain.'`');
        $table->setHeaders(['Całkowity czas testowania', $totalTime.' sec.']);
        $table->addRow(['Wszystkie symbole', count($unchecked)]);
        $table->addRow(['Odnalezione symbole', count($results['working'])]);
        $table->addRow(['Odnalezione symbole (tylko adfslight)', count($results['adfslight'])]);
        $table->addRow(['Błędne symbole', count($results['invalid'])]);
        $table->addRow(['Zakończono dostęp do aplikacji', count($results['end'])]);
        $table->addRow(['Nieoczekiwany wyjątek', count($results['exception'])]);
        $table->addRow(['Przerwa techniczna', count($results['break'])]);
        $table->addRow(['Aktualizacja bazy danych', count($results['db'])]);
        $table->addRow(['Nieznane', count($results['unknown'])]);
        $table->render();
    }
}
