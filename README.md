![alt text](https://accelasearch.com/wp-content/uploads/2022/11/Frame-13923578.png "AccelaSearch")
# Accelasearch Magento 2
This document is available in [English](README.md), [Italian](README.it.md).

## Installation
### Composer Installation
Install the module through [Composer](https://getcomposer.org/]):
```sh
composer require accelasearch/magento2
```
Enable the module:
```sh
bin/magento module:enable AccelaSearch_Search
```
Upgrade Magento's database and dependencies:
```sh
bin/magento setup:upgrade
```
Compile dependency injection:
```sh
bin/magento setup:di:compile
```
Compile static content:
```sh
bin/magento setup:static-content-deploy
```
Clear cache:
```sh
bin/magento cache:flush
```

### Manual Installation
Download package from GitHub:
```sh
wget https://github.com/accelasearch/magento2-module/archive/refs/heads/main.zip
```
Extract the zip file:
```sh
unzip main.zip
```
Copy its content into Magento's appropriate location:
```sh
cp -R main/* app/code/AccelaSearch/Search
```
Enable the module:
```sh
bin/magento module:enable AccelaSearch_Search
```
Upgrade Magento's database and dependencies:
```sh
bin/magento setup:upgrade
```
Compile dependency injection:
```sh
bin/magento setup:di:compile
```
Compile static content:
```sh
bin/magento setup:static-content-deploy
```
Clear cache:
```sh
bin/magento cache:flush
```


## Feed Configuration
___
### Feed Status
![Accelasearch Status](https://i.imgur.com/eGKjzAe.jpg)

Enables or disables feed export
___
### Use Vue Storefront
![Accelasearch Status](https://i.imgur.com/Kmn4Kcs.jpg)

If enabled it will be possible to specify a custom image size for the export process
___
### Export Directory
![Accelasearch Status](https://i.imgur.com/BFvyEs1.jpg)

Specifies destination directory for the XML feeds, relative to Magento 2 root
___
### Custom Base URL
![Accelasearch Status](https://i.imgur.com/lVPasvn.jpg)

Allows to specify a custom base URL, for example when using VUE to retrieve images of products
___
### Category
![Accelasearch Status](https://i.imgur.com/auLjdRz.jpg)

Specifies whether selected categories should be included or excluded

![Accelasearch Status](https://i.imgur.com/4zyeAD5.jpg)
___
### Exclude from Path generation
![Accelasearch Status](https://i.imgur.com/0iJoII1.jpg)

Specifies categories which shall be excluded by the category path generation process.
As an example, if some categories are specific for some language, they should be excluded from the feed of other Storeviews.
___
### Search
![Accelasearch Status](https://i.imgur.com/gnEE6JH.jpg)

Specifies endpoint where the JavaScript code of AccelaSearch can be accessed, as well as an optional CSS to be included for overriding and customization
___
### Fields
![Accelasearch Status](https://i.imgur.com/FugBM0S.jpg)

Specifies main fields for the feed. Some fields will be inherited form the GoogleShopping module and will not be used by AccelaSearch

![Accelasearch Status](https://i.imgur.com/ITloS6j.jpg)

Allows to specify additional values for custom fields which will be mapped as *key => value*

![Accelasearch Status](https://i.imgur.com/0AlUam0.jpg)

Allows to specify attributes with multiple values which will be exploded over multiple rows, as dictated by Google Shopping specifications
___
### Cron Configuration
![Accelasearch Status](https://i.imgur.com/oDdFIkN.jpg)

Allows to enable a cronjob to scheduler generation of feeds, as well as configure the relative cron expression and schedule the process as soon as possible.
___
### Notification
![Accelasearch Status](https://i.imgur.com/coph0BM.jpg)

Allows to specify a set of email addresses to which notify products affected by errors during the feed generation process
___
### Dynamic Price
![Accelasearch Status](https://i.imgur.com/OzLKnu9.jpg)

Exposes an endpoint which AccelaSearch can query to get dynamic prices depending on type of customer and currency code.

- Listing price: Attribute used to identify prices
- Public Visitor Type: Whether to export customer group
- Public Currency Code: Whether to export currency code
- Cache Lifetime: Time to live of dynamic price (used for caching)


## Comandi Manuali
Generates feeds as configured:
```sh
bin/magento accelasearch:generate:feed
```

## Cron
Default cron expression (configurable through user interface):
```sh
0 1 * * *
```

## NOTE
This modules generates a lock file under `var/locks` in order to prevent concurrent feed generation processes.
