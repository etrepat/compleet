<?php
namespace Compleet;

use Predis\Client as Client;

class CompleetTestCase extends \PHPUnit_Framework_TestCase {

  protected static $redis = null;

  protected $loader = null;

  protected $matcher = null;

  public static function setUpBeforeClass() {
    $conn = getenv('REDIS_URL') ?: 'tcp://127.0.0.1/6379?database=0';

    static::$redis = new Client($conn);
  }

  public static function tearDownAfterClass() {
    static::$redis->quit();

    static::$redis = null;
  }

  protected function importFromJSON($path) {
    $data = file_get_contents($path);

    $items = array_map(function($json) {
      return json_decode($json, true); }, explode(PHP_EOL, $data));

    return array_filter($items, function($item) {
      return !is_null($item) && !empty($item);
    });
  }

  public function setUp() {
    $this->loader = new Loader('venues'); // new Loader('venues', static::$redis)
    $this->loader->setConnection(static::$redis);

    $this->loader->clear();

    $this->matcher = new Matcher('venues');
    $this->matcher->setConnection(static::$redis);
  }

  public function testCanLoadAndMatch() {
    $items = $this->importFromJSON(__DIR__ . '/../samples/venues.json');

    $loaded = $this->loader->load($items);

    $this->assertEquals(7, count($loaded));

    $results = $this->matcher->matches('stad', ['limit' => 5]);
    $this->assertEquals(5, count($results));
    $this->assertEquals('Citi Field', $results[0]['term']);
  }

  public function testCanLoadAndMatchViaAliases() {
    $items = $this->importFromJSON(__DIR__ . '/../samples/venues.json');

    $loaded = $this->loader->load($items);
    $this->assertEquals(7, count($loaded));

    $results = $this->matcher->matches('land shark stadium', ['limit' => 5]);
    $this->assertEquals(1, count($results));
    $this->assertEquals('Sun Life Stadium', $results[0]['term']);

    $results = $this->matcher->matches('中国', ['limit' => 5]);
    $this->assertEquals(1, count($results));
    $this->assertEquals('中国佛山 李小龙', $results[0]['term']);

    $results = $this->matcher->matches('stadium', ['limit' => 5]);
    $this->assertEquals(5, count($results));
  }

  public function testLoaderCanAddItems() {
    $results = $this->matcher->matches('te', ['cache' => false]);
    $this->assertEquals(0, count($results));

    $this->loader->add(['id' => 1, 'term' => 'Testing this', 'score' => 10]);
    $this->loader->add(['id' => 2, 'term' => 'Something there', 'score' => 20]);
    $this->loader->add(['id' => 3, 'term' => 'Well, you should test this', 'score' => 5]);

    $results = $this->matcher->matches('we', ['cache' => false]);
    $this->assertEquals(1, count($results));
    $this->assertEquals([3], array_map(function($it) { return $it['id']; }, $results));

    $results = $this->matcher->matches('th', ['cache' => false]);
    $this->assertEquals(3, count($results));
    $this->assertEquals([2, 1, 3], array_map(function($it) { return $it['id']; }, $results));

    $results = $this->matcher->matches('fu', ['cache' => false]);
    $this->assertEquals(0, count($results));
  }

  public function testLoaderCanRemoveItems() {
    $results = $this->matcher->matches('th', ['cache' => false]);
    $this->assertEquals(0, count($results));

    $this->loader->add(['id' => 1, 'term' => 'Testing this', 'score' => 10]);
    $this->loader->add(['id' => 2, 'term' => 'Something there', 'score' => 20]);
    $this->loader->add(['id' => 3, 'term' => 'Well, you should test this', 'score' => 5]);

    $results = $this->matcher->matches('th', ['cache' => false]);
    $this->assertEquals(3, count($results));

    $this->loader->remove(['id' => 2]);

    $results = $this->matcher->matches('th', ['cache' => false]);
    $this->assertEquals(2, count($results));
    $this->assertEquals(1, $results[0]['id']);
  }

  public function testLoaderCanUpdateItems() {
    $results = $this->matcher->matches('te', ['cache' => false]);
    $this->assertEquals(0, count($results));

    $this->loader->add(['id' => 1, 'term' => 'Testing this', 'score' => 10]);
    $this->loader->add(['id' => 2, 'term' => 'Another Term', 'score' => 9]);
    $this->loader->add(['id' => 3, 'term' => 'Something different', 'score' => 5]);

    $results = $this->matcher->matches('te', ['cache' => false]);
    $this->assertEquals(2, count($results));
    $this->assertEquals('Testing this', $results[0]['term']);
    $this->assertEquals(10, $results[0]['score']);

    $this->loader->add(['id' => 1, 'term' => 'Updated', 'score' => 5]);

    $results = $this->matcher->matches('te', ['cache' => false]);
    $this->assertEquals(1, count($results));
    $this->assertEquals('Another Term', $results[0]['term']);
    $this->assertEquals(9, $results[0]['score']);
  }

  public function testMatcherHonorsLimitParameter() {
    $items = $this->importFromJSON(__DIR__ . '/../samples/venues.json');

    $loaded = $this->loader->load($items);
    $this->assertEquals(7, count($loaded));

    $results = $this->matcher->matches('sta');
    $this->assertEquals(5, count($results));

    $results = $this->matcher->matches('sta', ['limit' => 3]);
    $this->assertEquals(3, count($results));
  }

  public function testMatcherHonorsMinCompleteSetting() {
    $items = $this->importFromJSON(__DIR__ . '/../samples/venues.json');

    $loaded = $this->loader->load($items);
    $this->assertEquals(7, count($loaded));

    $results = $this->matcher->matches('sta', ['limit' => 5]);
    $this->assertEquals(5, count($results));

    $this->matcher->setMinComplete(4);

    $results = $this->matcher->matches('sta', ['limit' => 5]);
    $this->assertEquals(0, count($results));

    $results = $this->matcher->matches('stad', ['limit' => 5]);
    $this->assertEquals(5, count($results));
  }

}
