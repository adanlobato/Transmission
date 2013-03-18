# Transmission RPC Client

This library provides a Transmission RPC client written in PHP. It is built on top of [Buzz](https://github.com/kriswallsmith/Buzz) for
the communication layer and heavily inspired by the [PHP-Transmission-Class](https://github.com/brycied00d/PHP-Transmission-Class) project.

This project tries to bring to the community a Transmission RPC client using the best tools and practices present
in the ecosystem, such as namespaces, PSR-0, PSR-1, PSR-2, composer...

## Usage

### Getting started

The most common scenario is trying to connect to your Transmission on your ```http://localhost:9091```. It is as
easy as follows.

```php
<?php

use adanlobato\Transmission\Transmission;
use Buzz\Browser as Buzz;
use Buzz\Client\Curl;

$buzz = new Buzz(new Curl());
$transmission = new Transmission($buzz);

// Get session stats
$stats = $transmission->session()->getStats();
```

### Connect to a remote Transmission

Sometimes you might have the need to connect to a remote Transmission which is running on another machine.
You can connect easily by providing RPC's url.

```php
<?php
// ...

$buzz = new Buzz(new Curl());
$transmission = new Transmission($buzz, 'http://192.168.1.30:9091/transmission/rpc');

// ...
```

### Authentication

When you connect to Transmission RPC, you might have to provide your user and password credentials. This scenario can
be easily solved by registering a listener on ```Buzz```.

```php
<?php
// ...
use Buzz\Listener\BasicAuthListener;

$buzz = new Buzz(new Curl());
$buzz->addListener(new BasicAuthListener('username', 'password'));

$transmission = new Transmission($buzz);

// ...
```