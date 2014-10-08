<?php
namespace Compleet\Support\Traits;

use Predis\Client;

trait RedisConnectionManager {

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

}
