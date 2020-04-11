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
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Wulkanowy\SymbolsGenerator\Service\Filesystem;

class CheckCommand extends Command
{
    private const BASE_URL = 'vulcan.net.pl';
    private const TIMEOUT = 25;
    private const CONCURRENCY = 25;

    /** @var string */
    private $tmp;

    /** @var Filesystem */
    private $filesystem;

    /** @var Client */
    private $client;

    /** @var int */
    private $timeout;

    /** @var int */
    private $concurrency;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $domain = $input->getArgument("domain");
        $this->timeout = $input->getOption("timeout");
        $this->concurrency = $input->getOption("concurrency");
        $this->client = new Client([
            'base_uri' => 'https://uonetplus.' . $domain . '/',
            'allow_redirects' => ['track_redirects' => true]
        ]);

        $output->write('Testowanie...');
        $unchecked = json_decode($this->filesystem->getContents($this->tmp . '/unchecked-symbols.json'), true);

        $start = microtime(true);
        $results = $this->check($unchecked, $output);
        $totalTime = round(microtime(true) - $start, 2);

        $output->writeln("Testowanie... zakończone.");

        $this->saveResults($results, 'working');
        $this->saveResults($results, 'adfslight');
        $this->saveResults($results, 'invalid');
        $this->saveResults($results, 'end');
        $this->saveResults($results, 'exception');
        $this->saveResults($results, 'db');
        $this->saveResults($results, 'break');
        $this->saveResults($results, 'unknown');

        $this->showSummary($domain, $output, $unchecked, $results, $totalTime);

        return 0;
    }

    private function check(array $symbols, OutputInterface $o): array
    {
        $results = [
            'working' => [],
            'adfslight' => [],
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
            'concurrency' => $this->concurrency,
            'options' => ['timeout' => $this->timeout],
            'fulfilled' => function (ResponseInterface $response, $index) use ($s, $symbols, $amount, &$results) {
                $effectiveUrl = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);
                [$value, $prefix] = $this->getPrefixWithSymbol($amount, $symbols, $index);

                if (strpos($response->getBody(), 'Podany identyfikator klienta jest niepoprawny') !== false) {
                    $s->overwrite($prefix . '<fg=red>Nie udało się, bo brak dziennika</>');
                    $results['invalid'][] = $value;
                } elseif (strpos($response->getBody(), 'Zakończono świadczenie usługi dostępu do aplikacji') !== false) {
                    $s->overwrite($prefix . '<fg=red>Nie udało się, bo zakończono świadczenie usługi dostępu do aplikacji</>');
                    $results['end'][] = $value;
                } elseif (strpos($response->getBody(), 'Wystąpił nieoczekiwany wyjątek') !== false) {
                    $s->overwrite($prefix . '<fg=red>Nie udało się, bo wystąpił nieoczekiwany wyjątek</>');
                    $results['exception'][] = $value;
                } elseif (strpos($response->getBody(), 'Przerwa techniczna') !== false) {
                    $s->overwrite($prefix . '<fg=yellow>Przerwa techniczna</>');
                    $results['working'][] = $value;
                    $results['break'][] = $value;
                } elseif (strpos($response->getBody(), 'Trwa aktualizacja bazy danych') !== false) {
                    $s->overwrite($prefix . '<fg=yellow>Udało się, ale trwa aktualizacja bazy danych</>');
                    $results['working'][] = $value;
                    $results['db'][] = $value;
                } else {
                    if (strpos(end($effectiveUrl), 'adfslight') !== false) {
                        $results['adfslight'][] = $value;
                    }
                    $s->overwrite($prefix . '<fg=green>Udało się!</>');
                    $results['working'][] = $value;
                }
            },
            'rejected' => function (Throwable $reason, $index) use ($s, $symbols, $amount, &$results) {
                [$value, $prefix] = $this->getPrefixWithSymbol($amount, $symbols, $index);

                $s->overwrite($prefix . '<error>Error Processing Request: ' . $reason->getMessage() . '</error>');
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
            $this->tmp . '/checked-symbols-' . $type . '.json',
            json_encode($results[$type], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function showSummary(string $domain, OutputInterface $output, array $unchecked, array $results, int $totalTime)
    {
        $table = new Table($output->section());
        $table->setHeaderTitle('Podsumowanie dla `' . $domain . '`');
        $table->setHeaders(['Całkowity czas testowania', $totalTime . ' sec.']);
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
