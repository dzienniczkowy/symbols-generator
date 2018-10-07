<?php

namespace Wulkanowy\SymbolsGenerator;

use Colors\Color;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class Checker
{
    private $counties;

    private $client;

    public function __construct(array $counties, Client $client)
    {
        $this->counties = $counties;
        $this->client = $client;
    }

    public function getUp() : array
    {
        $c = new Color();
        $filtered = [];
        echo PHP_EOL;

        $requests = function ($items) {
            foreach ($items as $key => $value) {
                yield new Request('GET', $key);
            }
        };

        $t = $this->counties;

        $pool = new Pool($this->client, $requests($this->counties), [
            'concurrency' => 200,
            'fulfilled'   => function (ResponseInterface $response, $index) use ($c, $t, &$filtered) {
                $key = $index;
                $path = array_keys($t)[$index];
                $value = [$t[$path], $path];

                if (strpos($response->getBody(), 'Podany identyfikator klienta jest niepoprawny') !== false) {
                    echo $key.'. '.$path.' – '.$c('Nie udało się, bo brak dziennika')->fg('red');
                } elseif (strpos($response->getBody(), 'Zakończono świadczenie usługi dostępu do aplikacji') !== false) {
                    echo $key.'. '.$path.' – '.$c('Nie udało się, bo zakończono świadczenie usługi dostępu do aplikacji')->fg('red');
                } elseif (strpos($response->getBody(), 'Przerwa techniczna') !== false) {
                    echo $key.'. '.$path.' – '.$c('Przerwa techniczna')->fg('yellow');
                    $filtered[$key] = $value;
                } elseif (strpos($response->getBody(), 'Trwa aktualizacja bazy danych') !== false) {
                    echo $key.'. '.$path.' – '.$c('Udało się, ale trwa aktualizacja bazy danych')->fg('yellow');
                    $filtered[$key] = $value;
                } else {
                    echo $key.'. '.$path.' – '.$c('Udało się!')->fg('green');
                    $filtered[$key] = $value;
                }
                echo PHP_EOL;
            },
            'rejected' => function () {
                throw new \RuntimeException('Error Processing Request');
            },
        ]);

        $pool->promise()->wait();

        usort($filtered, function ($a, $b) {
            return $a[1] <=> $b[1];
        });

        return $filtered;
    }
}
