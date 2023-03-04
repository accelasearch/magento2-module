![alt text](https://accelasearch.com/wp-content/uploads/2022/11/Frame-13923578.png "AccelaSearch")

# Accelasearch Magento 2
This document is available in [English](README.md), [Italian](README.it.md).

## Installazione
### Tramite Composer
Installa il modulo tramite [Composer](https://getcomposer.org/]):
```sh
composer require accelasearch/magento2
```
Abilita il modulo:
```sh
bin/magento module:enable AccelaSearch_Search
```
Aggiorna il database e le dipendenze:
```sh
bin/magento setup:upgrade
```
Compila la dependency injection:
```sh
bin/magento setup:di:compile
```
Compila i contenuti statici:
```sh
bin/magento setup:static-content-deploy
```
Pulisci la cache:
```sh
bin/magento cache:flush
```

### Installazione Manuale
Scarica il pacchetto da GitHub:
```sh
wget https://github.com/accelasearch/magento2-module/archive/refs/heads/main.zip
```
Scompatta lo zip:
```sh
unzip main.zip
```
Copiane il contenuto:
```sh
cp -R main/* app/code/AccelaSearch/Search
```
Abilita il modulo:
```sh
bin/magento module:enable AccelaSearch_Search
```
Aggiorna il database e le dipendenze:
```sh
bin/magento setup:upgrade
```
Compila la dependency injection:
```sh
bin/magento setup:di:compile
```
Compila i contenuti statici:
```sh
bin/magento setup:static-content-deploy
```
Pulisci la cache:
```sh
bin/magento cache:flush
```


## Configurazione Feed
___
### Feed status
![Accelasearch Status](https://i.imgur.com/eGKjzAe.jpg)

Abilita o disabilita l'esportazione del feed
___
### Use VUE Storefront
![Accelasearch Status](https://i.imgur.com/Kmn4Kcs.jpg)

Se abilitato è possibile specificare una dimensione delle immagini custom in fase di export del feed
___
### Export Directory
![Accelasearch Status](https://i.imgur.com/BFvyEs1.jpg)

Specifica il path in cui depositare i file, relativo dalla root di Magento 2
___
### Custom Base URL
![Accelasearch Status](https://i.imgur.com/lVPasvn.jpg)

È possibile specificare un custom base URL, utile sopratutto per frontend VUE per recuperare le immagini dei prodotti
___
### Category
![Accelasearch Status](https://i.imgur.com/auLjdRz.jpg)

Specifica se includere o escludere le categorie sotto indicate

![Accelasearch Status](https://i.imgur.com/4zyeAD5.jpg)
___
### Exclude form Path Generation
![Accelasearch Status](https://i.imgur.com/0iJoII1.jpg)

Specifica categorie da escludere nella gerazione del path di categoria dei prodotti del feed.
Ad esempio se alcune categorie sono specifiche per una lingua, vanno escluse dal feed di quella Storeview.
___
### Search
![Accelasearch Status](https://i.imgur.com/gnEE6JH.jpg)

Specifica l'endpoint contenente il js di accelasearch ed eventuale file CSS da includere per stili specifici del sito
___
### Fields
![Accelasearch Status](https://i.imgur.com/FugBM0S.jpg)

Specifica i campi principali del feed. Alcuni campi sono ereditati dal modulo di googleshopping e non sono utilizzati da 
AccelaSearch

![Accelasearch Status](https://i.imgur.com/ITloS6j.jpg)
Sui campi custom e' possibile specificare dei valori aggiuntivi che verranno mappati come "chiave" => "valore"

![Accelasearch Status](https://i.imgur.com/0AlUam0.jpg)
Qui e' possibile specificare attributi con valori multipli da esplodere su piu' righe come su specifiche google shopping
___
### Cron
![Accelasearch Status](https://i.imgur.com/oDdFIkN.jpg)
Qui e' possibile abilitare il cron per la generazione schedulata dei feed, inoltre e' possibile configurare la relativa 
cron expression e schedulare una elaborazione al primo momento disponibile.
___
### NOTIFICATION
![Accelasearch Status](https://i.imgur.com/coph0BM.jpg)
Se abilitato e' possibile notificare a determinati indirizzi email eventuali prodotti con errori durante la generazione
del feed
___
### DYNAMIC PRICE
![Accelasearch Status](https://i.imgur.com/OzLKnu9.jpg)

Endpoint per fornire ad accelasearch i prezzi dinamici in base al customer type e currency code.

- Listing price : Specificare l'attributo usato a catalogo per identificare i prezzi
- Public Visitor Type : Esportare o meno il customer group
- Public Currency Code : Esportare o meno il currency code
- Cache Lifetime : Impostare il TTL della cache dei prezzi dimanici

## Comandi Manuali
```sh
bin/magento accelasearch:generate:feed
```
Genera i feed come da configurazione

## Cron Configuration
Configurabili a Backend di default la cron expression e' la seguente:
```sh
0 1 * * *
```

## Note
Il modulo genera un file di lock nella cartella var/locks per determinare evitare che piu' generazioni di feed vengano
eseguite in contemporanea
