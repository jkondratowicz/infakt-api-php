infakt-api
============

**DISCLAIMER:** This library is not properly tested (neither is it well documented), use at own risk!

---

This is a PHP 5.3+ API wrapper for the [InFakt API](http://infakt.pl/)

* Author: Jan Kondratowicz
* Created for project: [Fokus](http://getfokus.com)

---

##Requirements
* PHP 5.3+
* [CURL](http://php.net/manual/en/book.curl.php)
* API credentials.

##Installation

###Composer
Add the following package to `composer.json`:
```
"getfokus/infakt-api": "*"
```

##Basic usage

To call API just use method:
```
$response = $client->get($action, array $params);
```

###Example
```
<?php

$apiKey = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

try {
    $client = new InfaktApi\Client($apiKey);
    $response = $client->get('invoices');
    $parsed = json_decode($response);
    var_dump($parsed);
} catch (InfaktApi\Exception\UnauthorizedException $e) {
    echo 'Bad credentials';
}

```
