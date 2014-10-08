<?php
namespace Compleet;

use Compleet\Support\Str;

class Matcher extends Base {

  /**
   * Matches the given term against the indexed data in the Redis database.
   *
   * @param   string  $term
   * @param   array   $options
   * @return  array
   */
  public function matches($term, $options = array()) {
    $words = array_filter(explode(' ', Str::normalize($term)), function($w) {
      return (Str::size($w) >= $this->getMinComplete() && !in_array($w, $this->getStopWords()));
    });

    sort($words);

    if ( empty($words) ) return [];

    $limit = isset($options['limit']) ? $options['limit'] : 5;

    $cache = isset($options['cache']) ? $options['cache'] : true;

    $cacheExpiry = isset($options['expiry']) ? $options['expiry'] : 600;

    $cacheKey = $this->getCachePrefix() . ':' . implode('|', $words);

    if ( !$cache || !$this->redis()->exists($cacheKey) || $this->redis()->exists($cacheKey) == 0 ) {
      $interKeys = array_map(function($w) {
        return "{$this->getIndexPrefix()}:{$w}"; }, $words);

      $this->redis()->zinterstore($cacheKey, $interKeys);
      $this->redis()->expire($cacheKey, $cacheExpiry);
    }

    $ids = $this->redis()->zrevrange($cacheKey, 0, $limit - 1);

    if ( count($ids) === 0 ) return [];

    $results = $this->redis()->hmget($this->getDataPrefix(), $ids);
    $results = array_filter($results, function($res) { return !is_null($res); });

    return array_map(function($res) {
      return json_decode($res, true); }, $results);
  }

}
