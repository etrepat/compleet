<?php
namespace Compleet;

use Predis\Client;
use Compleet\Helpers\Ary;
use Compleet\Helpers\Str;

abstract class Base  {

  /**
   * Compleet version string.
   *
   * @var string
   */
  const VERSION = '1.0.0';

  /**
   * The redis client instance
   *
   * @var Predis\Client
   */
  protected $redis = null;

  /**
   * Redis connection parameters.
   *
   * @var array|string
   */
  protected $parameters = null;

  /**
   * Mininum amount of chars to consider autocompletion.
   *
   * @var int
   */
  protected $minComplete = 2;

  /**
   * Stop words to use for exclusion.
   *
   * @var array
   */
  protected $stopWords = ['vs', 'at', 'the'];

  /**
   * Base index prefix
   *
   * @var string
   */
  protected $indexName = 'compleet-index';

  /**
   * Data index prefix
   *
   * @var string
   */
  protected $dataIndexName = 'compleet-data';

  /**
   * Cache index prefix
   *
   * @var string
   */
  protected $cacheIndexName = 'compleet-cache';

  /**
   * @var string
   */
  protected $type;

  /**
   * Create a new base instance.
   *
   * @param  string         $type
   * @param  array          $options
   * @param  Predis\Client  $redis
   * @return void
   */
  public function __construct($type, Client $redis = null) {
    $this->type = $type;

    if ( !is_null($redis) ) $this->setConnection($redis);
  }

  /**
   * Sets and resolves a new connection to redis. It accepts a previously
   * instatiated Predis\Client or anything that the Predis\Client constructor
   * deals with: A parameters array or a connection string.
   *
   * @param   mixed   $client
   * @return  Predis\Client
   */
  public function setConnection($client) {
    if ( is_string($client) || is_array($client) ) {
      $this->redis = null;
      $this->parameters = $client;
    } else {
      $this->redis = $client;
    }

    return $this->resolveConnection();
  }

  /**
   * Returns the current connection instance in use.
   *
   * @return Predis\Client;
   */
  public function getConnection() {
    return $this->redis;
  }

  /**
   * Returns the current connection instance or instatiates a new one from
   * the provided parameters, the environment or localhost.
   *
   * @return Predis\Client
   */
  public function resolveConnection() {
    if ( !is_null($this->redis) ) return $this->redis;

    $parameters = $this->parameters ?: getenv('REDIS_URL') ?: 'tcp://127.0.0.1/6379?database=0';

    $this->redis = new Client($parameters);

    return $this->redis;
  }

  /**
   * Alias for `resolveConnection()`.
   *
   * @return Predis\Client
   */
  public function redis() {
    return $this->resolveConnection();
  }

  /**
   * @return int
   */
  public function getMinComplete() {
    return $this->minComplete;
  }

  /**
   * @return void
   */
  public function setMinComplete($min) {
    $this->minComplete = intval($min);
  }

  /**
   * Return the stop words array.
   *
   * @return array
   */
  public function getStopWords() {
    return $this->stopWords;
  }

  /**
   * Set the stop words array.
   *
   * @param   array $words
   * @return  void
   */
  public function setStopWords(array $words) {
    $this->stopWords = Ary::flatten(array_map(function($w) {
      return Str::normalize($w); }, $words));
  }

  /**
   * Returns the base index prefix name.
   *
   * @return string
   */
  public function getIndexPrefix() {
    return "{$this->indexName}:{$this->type}";
  }

  /**
   * Returns the data index prefix name.
   *
   * @return string
   */
  public function getDataPrefix() {
    return "{$this->dataIndexName}:{$this->type}";
  }

  /**
   * Returns the cache index prefix name.
   *
   * @return string
   */
  public function getCachePrefix() {
    return "{$this->indexName}:{$this->type}";
  }

}
