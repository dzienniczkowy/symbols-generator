# Generator symboli dla wulkanowego

[![Latest Stable Version](https://poser.pugx.org/wulkanowy/symbols-generator/version?format=flat-square)](https://packagist.org/packages/wulkanowy/symbols-generator)
[![StyleCI](https://styleci.io/repos/88377290/shield?branch=master)](https://styleci.io/repos/88377290)

## Instalacja

Projekt można zainstalować u siebie Composerem:

```bash
$ composer create-project wulkanowy/symbols-generator
```

lub najpierw klonując projekt przy użyciu gita a następnie instalując zależności Composerem:

```bash
$ git clone https://github.com/wulkanowy/symbols-generator
$ cd symbols-generator
$ composer install
```

## Użycie

Ze strony [Rejestru TERYT](http://eteryt.stat.gov.pl/eTeryt/rejestr_teryt/udostepnianie_danych/baza_teryt/uzytkownicy_indywidualni/pobieranie/pliki_pelne.aspx)
należy pobrać do katalogu projektu dane oznaczone jako NTS i wykonać polecenie:

```bash
$ bin/console generate fakelog.cf --timeout 30 --concurrency 1
```

Opis wszystkich dostępnych opcji można sprawdzić przez wbudowany help: 
```bash
$ bin/console generate --help
```
