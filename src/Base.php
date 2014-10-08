<?php
namespace Compleet;

use Predis\Client;
use Compleet\Support\Ary;
use Compleet\Support\Str;

abstract class Base  {

  use \Compleet\Support\Traits\RedisConnectionManager;

  /**
   * Compleet version string.
   *
   * @var string
   */
  const VERSION = '1.0.0';

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
   * Data type (class of data/bucket)
   *
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
   * Gets the current instance data type
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Sets the current instance data type
   *
   * @param   string $type
   * @return  void
   */
  public function setType($type) {
    $this->type = Str::normalize($type);
  }

  /**
   * Returns the min-complete setting.
   *
   * @return int
   */
  public function getMinComplete() {
    return $this->minComplete;
  }

  /**
   * Set the default minimum characters for a word to be considered.
   *
   * @param   int $min
   * @return  void
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
   * Returns the base index name.
   *
   * @return string
   */
  public function getIndexName() {
    return $this->indexName;
  }

  /**
   * Sets the base index name.
   *
   * @param   string $value
   * @return  void
   */
  public function setIndexName($value) {
    $this->indexName = $value;
  }

  /**
   * Returns the data index name.
   *
   * @return string
   */
  public function getDataIndexName() {
    return $this->dataIndexName;
  }

  /**
   * Sets the data index name.
   *
   * @param   string $value
   * @return  void
   */
  public function setDataIndexName($value) {
    $this->dataIndexName = $value;
  }

  /**
   * Returns the cache index name.
   *
   * @return string
   */
  public function getCacheIndexName() {
    return $this->cacheIndexName;
  }

  /**
   * Sets the cache index name.
   *
   * @param   string $value
   * @return  void
   */
  public function setCacheIndexName($value) {
    $this->cacheIndexName = $value;
  }

  /**
   * Returns the base index prefix name.
   *
   * @return string
   */
  public function getIndexPrefix() {
    return $this->getIndexName() . ':' . $this->getType();
  }

  /**
   * Returns the data index prefix name.
   *
   * @return string
   */
  public function getDataPrefix() {
    return $this->getDataIndexName() . ':' . $this->getType();
  }

  /**
   * Returns the cache index prefix name.
   *
   * @return string
   */
  public function getCachePrefix() {
    $this->getCacheIndexName() . ':' . $this->getType();
  }

}
