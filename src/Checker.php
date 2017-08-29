<?php

namespace Wulkanowy;

use Colors\Color;
use GuzzleHttp\Client;

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

        foreach ($this->counties as $key => $value) {
            $path = (new StringFormatter($value[1]))
                    ->latinize()
                    ->lowercase()
                    ->removeDashes()
                    ->removeSpaces()
                    ->get();

            $response = $this->client->request('GET', $path);
            $body = $response->getBody();

            if (strpos($body, 'Podany identyfikator klienta jest niepoprawny') === false) {
                 echo $key.'. '.$path.' – '. $c('Udało się!')->fg('green');
                $filtered[] = $value;
            } else {
                 echo $key.'. '.$path.' – '. $c('Nie udało się!')->fg('red');
            }
             echo PHP_EOL;
        }
         echo PHP_EOL;

        return $filtered;
    }
}
