<?php
namespace Compleet;

use Predis\Client as Client;
use Compleet\Loader;
use Compleet\Matcher;

class CompleetTestCase extends \PHPUnit_Framework_TestCase {

  protected static $redis;

  protected $json = 'samples/venues.json';

  public static function setUpBeforeClass() {
    static::$redis = new Client('tcp://127.0.0.1:6379?database=0');
  }

  public function testCanLoadAndMatch() {
    $items = $this->loadFromJSON(__DIR__ . '/../' . $this->json);

    $loader = new Loader('venues', static::$redis);
    $loaded = $loader->load($items);

    $this->assertEquals(7, count($loaded));

    $matcher = new Matcher('venues', static::$redis);
    $results = $matcher->matches('stad')->limit(5)->get();

    $this->assertEquals(5, count($results));
    $this->assertEquals('Citi Field', $results[0]['term']);
  }

  public function testCanLoadAndMatchViaAliases() {
    $items = $this->loadFromJSON(__DIR__ . '/../' . $this->json);

    $loader = new Loader('venues', static::$redis);
    $loaded = $loader->load($items);

    $this->assertEquals(7, count($loaded));

    $matcher = new Matcher('venues', static::$redis);

    $results = $matcher->matches('land shark stadium')->limit(5)->get();
    $this->assertEquals(1, count($results));
    $this->assertEquals('Sun Life Stadium', $results[0]['term']);

    $results = $matcher->matches('中国')->limit(5)->get();
    $this->assertEquals(1, count($results));
    $this->assertEquals('中国佛山 李小龙', $results[0]['term']);

    $results = $matcher->matches('stadium')->limit(5)->get();
    $this->assertEquals(5, count($results));
  }

  public function testCanRemoveItems() {
    $loader = new Loader('venues', static::$redis);
    $matcher = new Matcher('venues', static::$redis);

    $loader->load([]);

    $results = $matcher->matches('te')->noCache()->get();
    $this->assertEquals(0, count($results));

    $loader->add(['id' => 1, 'term' => 'Testing this', 'score' => 10]);
    $results = $matcher->matches('te')->noCache()->get();
    $this->assertEquals(1, count($results));

    $loader->remove(['id' => 1]);
    $results = $matcher->matches('te')->noCache()->get();
    $this->assertEquals(0, count($results));
  }

  public function testCanUpdateItems() {
    $loader = new Loader('venues', static::$redis);
    $matcher = new Matcher('venues', static::$redis);

    $loader->load([]);

    $loader->add(['id' => 1, 'term' => 'Testing this', 'score' => 10]);
    $loader->add(['id' => 2, 'term' => 'Another Term', 'score' => 9]);
    $loader->add(['id' => 3, 'term' => 'Something different', 'score' => 5]);

    $results = $matcher->matches('te')->noCache()->get();
    $this->assertEquals(2, count($results));
    $this->assertEquals('Testing this', $results[0]['term']);
    $this->assertEquals(10, $results[0]['score']);

    $loader->add(['id' => 1, 'term' => 'Updated', 'score' => 5]);

    $results = $matcher->matches('te')->noCache()->get();
    $this->assertEquals(1, count($results));
    $this->assertEquals('Another Term', $results[0]['term']);
    $this->assertEquals(9, $results[0]['score']);
  }

  protected function loadFromJSON($path) {
    $data = file_get_contents($path);

    return array_filter(array_map(function($json) {
      return json_decode($json, true);
    }, explode(PHP_EOL, $data)), function($ary) {
      return !is_null($ary);
    });
  }

}
