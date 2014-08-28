<?php
namespace Compleet\Cli;

use Predis\Client;
use Compleet\Base as Compleet;
use Compleet\Matcher;
use Compleet\Loader;
use Compleeet\Util\Str;

class App {

  protected $parser = null;

  protected $redis = null;

  protected $stopWords = array();

  protected $version = Compleet::VERSION;

  public function __construct() {
    $this->version = $this->getVersionString();

    $this->parser = new OptionParser;
    $this->parser->banner("Usage:\n    compleet [OPTIONS] COMMAND\n")
      ->separator('')
      ->on('-r', '--redis'      , OptionParser::OPTION_VALUE_REQUIRED , 'Redis connection string (tcp://127.0.0.1:6379?database=0)')
      ->on('-s', '--stop-words' , OptionParser::OPTION_VALUE_REQUIRED , 'Path to file containing a list of stop words')
      ->on('-h', '--help'       , OptionParser::OPTION_VALUE_NONE     , 'Show this help screen')
      ->on('-v', '--version'    , OptionParser::OPTION_VALUE_NONE     , 'Display version number')
      ->separator('')
      ->separator('Commands are:')
      ->separator("\r    load   TYPE        Replaces collection specified by TYPE with items read from stdin in the JSON lines format.")
      ->separator("\r    add    TYPE        Adds items to collection specified by TYPE read from stdin in the JSON lines format.")
      ->separator("\r    remove TYPE        Removes items from collection specified by TYPE read from stdin in the JSON lines format. Items only require an 'id', all other fields are ignored.")
      ->separator("\r    query  TYPE QUERY  Removes items from collection specified by TYPE read from stdin in the JSON lines format. Items only require an 'id', all other fields are ignored.")
      ->separator('');
  }

  public function run(array $arguments) {
    try {
      $options = $this->parser->parse($arguments);

      if ( isset($options['version']) ) {
        fprintf(STDOUT, "%s\n", $this->getVersionString());

        return 0;
      }

      if ( isset($options['help']) ) {
        fprintf(STDOUT, "%s", $this->parser);

        return 0;
      }

      if ( isset($options['redis']) ) {
        $this->redis = new Client($options['redis']);
      }

      if ( isset($options['stop-words']) ) {
        $this->stopWords = array_filter(array_map(function($word) {
          return Str::normalize($word);
        }, explode(PHP_EOL, file_get_contents($options['stop-words']))), function($word) {
          return !empty($word);
        });
      }

      $command = array_shift($arguments);

      if ( is_null($command) )
        throw new OptionParserException("! A command is mandatory");

      $method = "{$command}Command";

      if ( !method_exists($this, $method) )
        throw new OptionParserException("! Unknown command: {$command}");

      return call_user_func_array(array($this, $method), $arguments);
    } catch (OptionParserException $e) {
      fprintf(STDERR, "%s\n%s\n", $e->getMessage(), $this->parser->help());

      return -1;
    } catch (Exception $e) {
      fprintf(STDERR, "An unknown error ocurred: %s\n", $e->getMessage());

      return -1;
    }
  }

  public function loadCommand($type) {
    $items = $this->getItemsFromSTDIN();

    $loaded = [];

    if ( count($items) > 0 ) {
      $loader = $this->getCompleetLoader($type);
      $loaded = $loader->load($items);
    }

    fprintf(STDOUT, "Loaded a total of %d items\n", count($loaded));

    return 0;
  }

  public function addCommand($type) {
    $items = $this->getItemsFromSTDIN();

    $loaded = 0;

    if ( count($items) > 0 ) {
      $loader = $this->getCompleetLoader($type);

      foreach($items as $item) {
        $loader->add($item);
        $loaded = $loaded + 1;
      }
    }

    fprintf(STDOUT, "Loaded a total of %d items\n", $loaded);

    return 0;
  }

  public function removeCommand($type) {
    $items = $this->getItemsFromSTDIN();

    $loaded = 0;

    if ( count($items) > 0 ) {
      $loader = $this->getCompleetLoader($type);

      foreach($items as $item) {
        $loader->remove($item);
        $loaded = $loaded + 1;
      }
    }

    fprintf(STDOUT, "Removed a total of %d items\n", $loaded);

    return 0;
  }

  public function queryCommand($type, $query) {
    fprintf(STDOUT, "> Querying '%s' for '%s'\n", $type, $query);

    $matcher = $this->getCompleetMatcher($type);

    $results = $matcher->matches($query, ['limit' => 0]);
    foreach($results as $item)
      fprintf(STDOUT, "%s\n", json_encode($item));

    fprintf(STDOUT, "> Found %d matches for '%s'\n", count($results), $query);

    return 0;
  }

  protected function getVersionString() {
    return implode(PHP_EOL, [
      'Compleet v'.Compleet::VERSION.' (c) '.date('Y').' Estanislau Trepat',
      'Redis-backed service for fast autocompleting'
    ]);
  }

  protected function getCompleetLoader($type) {
    $loader = new Loader($type);

    if ( !is_null($this->redis) )
      $loader->setConnection($this->redis);

    if ( !empty($this->stopWords) )
      $loader->setStopWords($this->stopWords);

    return $loader;
  }

  protected function getCompleetMatcher($type) {
    $matcher = new Matcher($type);

    if ( !is_null($this->redis) )
      $matcher->setConnection($this->redis);

    if ( !empty($this->stopWords) )
      $matcher->setStopWords($this->stopWords);

    return $matcher;
  }

  protected function getItemsFromSTDIN() {
    $data = explode(PHP_EOL, file_get_contents("php://stdin"));

    $items = array_map(function($json) {
      return json_decode($json, true); }, $data);

    return array_filter($items, function($item) { return !is_null($item); });
  }

}
