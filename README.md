# Compleet

[![Latest Stable Version](https://poser.pugx.org/etrepat/compleet/v/stable.svg)](https://packagist.org/packages/etrepat/compleet)
[![Total Downloads](https://poser.pugx.org/etrepat/compleet/downloads.svg)](https://packagist.org/packages/etrepat/compleet)
[![License](https://poser.pugx.org/etrepat/compleet/license.svg)](https://packagist.org/packages/etrepat/compleet)
[![Build Status](https://travis-ci.org/etrepat/compleet.svg?branch=master)](https://travis-ci.org/etrepat/compleet)

Compleet is a PHP port of the awesome [Soulmate](https://github.com/seatgeek/soulmate) ruby gem, which was written by Eric Waller. All credit should go to the original library author.

This library will help you solve the common problem of developing a fast autocomplete feature. It uses Redis's sorted sets to build an index of partially completed words and the corresponding top matching items, and provides a simple interface to query them. Compleet finishes your sentences.

Compleet requires [PHP](http://www.php.net) >= 5.4 (or [HHVM](http://www.hhvm.com) >= 3.2) and its only dependencies are [Composer](http://getcomposer.org) itself and the fantastic [Predis](https://github.com/nrk/predis/) PHP client library for Redis.

## Getting Started

Compleet is distributed as a composer package, so kick things off by adding it as a requirement to your `composer.json` file:

```json
{
  "require": {
    "etrepat/compleet": "~1.0"
  }
}
```

Then update your packages by running `composer update` or install with `composer install`.

## Usage

Compleet is designed to be simple and fast, and its main features are:

* Provide suggestions for multiple types of items in a single query.
* Sort results by user-specified score, lexycographically otherwise.
* Store arbitrary metadata for each item. You may store url's, thumbail paths, etc.

An *item* is a simple JSON object that looks like:

```json
{
  "id": 3,
  "term": "Citi Field",
  "score": 81,
  "data": {
    "url": "/citi-field-tickets/",
    "subtitle": "Flushing, NY"
  }
}
```

Where `id` is a unique identifier (within the specific type), `term` is the phrase you wish to provide completions for, `score` is a user-specified ranking metric (redis will order things lexicographically for items with the same score), and `data` is an optional container for metadata you'd like to return when this item is matched.

### Managing items

Before being able to perform any autocomplete search we must first load some data into our redis database. To feed data into a Redis server instance for later querying, Compleet provides the `Loader` class:

```php
require __DIR__ . '/vendor/autoload.php';

$loader = new Compleet\Loader('venues');
```

The constructor parameter is the type of the items we are actually going to add/remove or load. We'll later use this same type for search. This is used so we can add several kinds of completeley differentiated data and index it into Redis separately.

By default a `Compleet\Loader` object will instatiate a connection to a local redis instance as soon as it is needed. If you wish to change this behaviour, you may either provide a connection into the constructor:

```php
require __DIR__ . '/vendor/autoload.php';

$redis = new Predis\Client('tcp://127.0.0.1/6379?database=0');

$loader = new Compleet\Loader('venues', $redis);
```

or use the `setConnection` method:

```php
require __DIR__ . '/vendor/autoload.php';

$redis = new Predis\Client('tcp://127.0.0.1/6379?database=0');

$loader = new Compleet\Loader('venues');
$loader->setConnection($redis);
```

#### Loading items

Now that we have a loader object in place we probably want to use it to load a bunch of items into our redis database. Imagine we have the following PHP item array (which follows the previous JSON structure):

```php
$items = array(
  array(
    'id' => 1,
    'term' => 'Dodger Stadium',
    'score' => 85,
    'data' => array(
      'url' => '/dodger-stadium/tickets',
      'subtitle' => 'Los Angeles, CA'
    )
  ),
  array(
    'id' => 28,
    'term' => 'Angel Stadium',
    'score' => 85,
    'data' => array(
      'url' => '/angel-stadium- tickets/',
      'subtitle' => 'Anaheim, CA'
    )
  )
  ... etc ...
);
```

To load these items into Redis for later querying and previously clearing all existing data we have the `load` method:

```php
$loader->load($items);
```

#### Adding items

We may add a single item in a similar fashion with the `add` method:

```php
$item = array(
  'id' => 30,
  'term' => 'Chase Field',
  'score' => 85,
  'data' => array(
    'url' => '/chase-field-ticket/',
    'subtitle' => 'Phoenix, AZ'
  )
);

$loader->add($item);
```

The `add` method appends items individually without previously clearing the index.

For both the `load` and `add` methods, each item must supply the `id` and `term` array keys. All other attributes are optional.

#### Removing items

Similarly if we need to remove an individual item, the `remove` method will do just that:

```php
$item = array(
  'id' => 30,
  'term' => 'Chase Field',
  'score' => 85,
  'data' => array(
    'url' => '/chase-field-ticket/',
    'subtitle' => 'Phoenix, AZ'
  )
);

$loader->remove($item);
```

Only the `id` key will be used and is actually mandatory.

#### Clearing the index

To wipe out all of the previously indexed data we may use the `clear` method:

```php
$loader->clear();
```

### Querying

Analogous to the `Compleet\Loader` class, Compleet provides the `Matcher` class which will allow us to query against previously indexed data. It works pretty similarly and accepts the same constructor arguments:

```php
require __DIR__ . '/vendor/autoload.php';

$redis = new Predis\Client('tcp://127.0.0.1/6379?database=0');

$matcher = new Compleet\Matcher('venues', $redis);
// or:
//  $matcher = new Compleet\Matcher('venues');
//  $matcher->setConnection($redis);
```

Again, the first constructor parameter is the type of the items we are actually going query against.

Then, the `matches` method will allow us to query the supplied term against the indexed data:

```php
$results = $matcher->matches('stad');
```

This will perform a search against the index of partially completed words for the `venues` type and it will return an array of matching items for the term `stad`. The resulting array will be sorted by the supplied score or alphabetically if none was given.

The `matches` method also supports passing an array of options as a second argument:

```php
$queryOptions = array(
  // resultset size limit (defaults to 5)
  'limit' => 5,

  // whether to cache the results or not (defaults to true)
  'cache' => true

  // cache expiry time in seconds (defaults to 600)
  'expiry' => 600
);

$results = $matcher->matches('stad', $queryOptions);
```

Setting the `limit` option to `0` will return *all* results which match the provided term.

Matching a single term against multiple types of items should be easy enough:

```php
$types = array('products', 'categories', 'brands');

$results = array();

foreach($types as $type) {
  $matcher = new Compleet\Matcher($type);
  $results[$type] = $matcher->matches('some term');
}
```

## Working with the CLI

Compleet also provides a CLI utility, the `compleet` script, for easy data indexing from JSON documents/files. It may also be used to conduct queries for testing purposes. Like many composer packages which supply a binary, it will probably be placed under the `vendor/bin` folder of your project.

To load data into redis from the CLI, you can pipe items from a JSON file into the `compleet load TYPE` command.

Here's a sample `venues.json` (one JSON item per line):

```json
{"id":1,"term":"Dodger Stadium","score":85,"data":{"url":"\/dodger-stadium-tickets\/","subtitle":"Los Angeles, CA"}}
{"id":28,"term":"Angel Stadium","score":85,"data":{"url":"\/angel-stadium-tickets\/","subtitle":"Anaheim, CA"}}
{"id":30,"term":"Chase Field ","score":85,"data":{"url":"\/chase-field-tickets\/","subtitle":"Phoenix, AZ"}}
{"id":29,"term":"Sun Life Stadium","score":84,"data":{"url":"\/sun-life-stadium-tickets\/","subtitle":"Miami, FL"}}
{"id":2,"term":"Turner Field","score":83,"data":{"url":"\/turner-field-tickets\/","subtitle":"Atlanta, GA"}}
```

And here's the load command (The `compleet` utility will assume redis is running locally on the default port, or you can specify a redis connection string with the `--redis` argument):

    $ vendor/bin/compleet load venues < venues.json

Running `compleet -h` will list all the operations which are supported by the CLI and its arguments. All operations that may be performed programatically can be run from the `compleet` utility.

## Using Compleet with your framework

Compleet is a standalone composer package and should work out of the box regardless of the PHP framework you use. In fact, none is needed.

### Using Compleet with Laravel

A Compleet integration for [Laravel](http://www.laravel.com) >= 4.2 is provided in the [Laravel Compleet](https://github.com/etrepat/laravel-compleet.git) package. It will help reduce the amount of boilerplate code needed to make the autocomplete functionality in your project useful and adds several nice integrations such as: global config, artisan commands, redis connection reuse, controller code, routes, etc.

Take a look at the [Laravel Compleet](https://github.com/etrepat/laravel-compleet.git) project page for more information on how to use Compleet with your [Laravel](http://www.laravel.com) applications.

## Contributing

Thinking of contributing? Maybe you've found some nasty bug? That's great news!

1. Fork & clone the project: `git clone git@github.com:your-username/compleet.git`.
2. Run the tests and make sure that they pass with your setup: `phpunit`.
3. Create your bugfix/feature branch and code away your changes. Add tests for your changes.
4. Make sure all the tests still pass: `phpunit`.
5. Push to your fork and submit new a pull request.

## License

Compleet is licensed under the terms of the [MIT License](http://opensource.org/licenses/MIT)
(See LICENSE file for details).

---

Coded by [Estanislau Trepat (etrepat)](http://etrepat.com). I'm also
[@etrepat](http://twitter.com/etrepat) on twitter.
