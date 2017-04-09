<?php

namespace Wulkanowy;

class Checker
{
    private $client;

    public function __construct(array $couties, $client)
    {
        $this->couties = $couties;
        $this->client = $client;
    }

    public function getUp() : array
    {
        $filtered = [];
        // echo PHP_EOL;

        foreach ($this->couties as $key => $value) {
            $path = (new StringFormatter($value[1]))
                    ->latinize()
                    ->lowercase()
                    ->removeDashes()
                    ->removeSpaces()
                    ->get();

            $response = $this->client->request('GET', $path);
            $body = $response->getBody();

            if (strpos($body, 'Podany identyfikator klienta jest niepoprawny') === false) {
                // echo $key.' '.$path.' – Udało się!';
                $filtered[] = $value;
            } else {
                // echo $key.' '.$path.' – Nie udało się!';
            }
            // echo PHP_EOL;
        }
        // echo PHP_EOL;

        return $filtered;
    }
}
