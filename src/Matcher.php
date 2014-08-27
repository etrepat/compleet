<?php
namespace Compleet;

use Compleet\Helpers\Str;

class Matcher extends Base {

  protected $limit = 5;

  protected $cache = true;

  protected $expiry = 600;

  protected $words = [];

  public function matches($term) {
    $words = explode(' ', Str::normalize($term));

    $this->words = array_filter($words, function($w) {
      return (Str::size($w) >= $this->getMinComplete() || !in_array($w, $this->getStopWords()));
    });

    sort($this->words);

    return $this;
  }

  public function get() {
    if ( empty($this->words) ) return [];

    $cacheKey = $this->getCachePrefix() . ':' . implode('|', $this->words);

    if ( !$this->cache || !$this->redis->exists($cacheKey) || $this->redis->exists($cacheKey) == 0 ) {
      $interKeys = array_map(function($w) {
        return "{$this->getIndexPrefix()}:{$w}"; }, $this->words);

      $this->redis->zinterstore($cacheKey, $interKeys);
      $this->redis->expire($cacheKey, $this->expiry);
    }

    $ids = $this->redis->zrevrange($cacheKey, 0, $this->limit - 1);

    if ( count($ids) === 0 ) return [];

    $results = $this->redis->hmget($this->getDataPrefix(), $ids);
    $results = array_filter($results, function($res) { return !is_null($res); });

    return array_map(function($res) {
      return json_decode($res, true); }, $results);
  }

  public function limit($limit) {
    $this->limit = $limit;

    return $this;
  }

  public function cache($enable) {
    $this->cache = $enable;

    return $this;
  }

  public function expires($expiry) {
    $this->expiry = $expiry;

    return $this;
  }

  public function noCache() {
    return $this->cache(false);
  }

}
