infakt-api
============

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

$clientId = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$clientSecret = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$username = 'example@email.com';
$password = 'SECRET';

try {
    $client = new InfaktApi\Client($clientId, $clientSecret);
    $client->authorize($username, $password);
    $response = $client->get('settings/user_data');
    $parsed = json_decode($response);
    var_dump($parsed);
} catch (InfaktApi\Exception\UnauthorizedException $e) {
    echo 'Bad credentials';
}

```