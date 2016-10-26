guzzle-hsts
===========

[![Build Status](https://img.shields.io/travis/CheatCodes/guzzle-hsts/master.svg?style=flat-square)](https://travis-ci.org/CheatCodes/guzzle-hsts)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/CheatCodes/guzzle-hsts.svg?style=flat-square)](https://scrutinizer-ci.com/g/CheatCodes/guzzle-hsts/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/cheatcodes/guzzle-hsts.svg?style=flat-square)](https://scrutinizer-ci.com/g/CheatCodes/guzzle-hsts/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/cheatcodes/guzzle-hsts.svg?style=flat-square)](https://packagist.org/packages/cheatcodes/guzzle-hsts)

This is a [Guzzle](https://github.com/guzzle/guzzle) middleware to handle
[HTTP Strict Transport Security](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security).

Installation
------------

Make sure you have [Composer](https://getcomposer.org/) installed and add guzzle-hsts as a dependency.

```bash
$ composer require cheatcodes/guzzle-hsts
```

Usage
-----

Make sure Guzzle uses the `CheatCodes\GuzzleHsts\HstsMiddleware::handler()` as a middleware by pushing it onto the
handler stack. After that, for all known HSTS hosts all requests to http will be automatically rewritten to https.

An example:

```php
use CheatCodes\GuzzleHsts\HstsMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

$stack = HandlerStack::create();

// Add HSTS middleware to the handler
$stack->push(HstsMiddleware::handler());

// Initialize the Guzzle client with the handler
$client = new Client(['handler' => $stack]);

// Make a request to a https host with HSTS enabled
$client->request('GET', 'https://hsts.badssl.com/');

// Later requests to the same hosts will automatically be rewritten to https
$client->request('GET', 'http://hsts.badssl.com/');
```

License
-------

MIT