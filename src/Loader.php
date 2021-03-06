<?php
namespace Compleet;

use Compleet\Support\Str;

class Loader extends Base {

  /**
   * Loads supplied items array into Redis.
   *
   * @param   array $items
   * @return  array
   */
  public function load(array $items) {
    // Clear all the sorted sets and data store for the current type
    $this->clear();

    // Redis can continue serving cached requests for this type while the reload is
    // occuring. Some requests may be cached incorrectly as empty set (for requests
    // which come in after the above delete, but before the loading completes). But
    // everything will work itself out as soon as the cache expires again.
    foreach($items as $item) $this->add($item, true);

    return $items;
  }

  /**
   * Clear redis indexed item data.
   *
   * @return void
   */
  public function clear() {
    // Delete the sorted sets for the current type
    $phrases = $this->redis()->smembers($this->getIndexPrefix());

    $this->redis()->pipeline(function($pipe) use ($phrases) {
      foreach($phrases as $p) $pipe->del("{$this->getIndexPrefix()}:{$p}");
      $pipe->del($this->getIndexPrefix());
    });

    // Delete the data stored for this type
    $this->redis()->del($this->getDataPrefix());
  }

  /**
   * Adds a single item into Redis.
   *
   * @param   array   $item
   * @param   bool    $skipDuplicateChecks
   * @return  void
   */
  public function add(array $item, $skipDuplicateChecks = false) {
    if ( !(array_key_exists('id', $item) && array_key_exists('term', $item)) )
      throw new ItemFormatException('Items must at least specify both an id and a term.');

    // Set default options for item array
    $item = array_merge(['score' => 0], $item);

    // kill any old items with this id if needed
    if ( !$skipDuplicateChecks ) $this->remove(['id' => $item['id']]);

    $this->redis()->pipeline(function($pipe) use ($item) {
      // store the raw data in a separate key to reduce memory usage
      $pipe->hset($this->getDataPrefix(), $item['id'], json_encode($item));

      $prefixes = $this->prefixes($item);
      if ( count($prefixes) > 0 ) {
        foreach($prefixes as $p) {
          // remember this prefix in a master set
          $pipe->sadd($this->getIndexPrefix(), $p);

          // store the id of this term in the index
          $pipe->zadd("{$this->getIndexPrefix()}:{$p}", $item['score'], $item['id']);
        }
      }
    });
  }

  /**
   * Removes the supplied item from Redis.
   * It only cares about an item's id, but for consistency takes an array
   *
   * @param   array $item
   * @return  void
   */
  public function remove(array $item) {
    $stored = $this->redis()->hget($this->getDataPrefix(), $item['id']);

    if ( is_null($stored) ) return;

    $item = json_decode($stored, true);

    // undo the add operations
    $this->redis()->pipeline(function($pipe) use ($item) {
      $pipe->hdel($this->getDataPrefix(), $item['id']);

      $prefixes = $this->prefixes($item);
      if ( count($prefixes) > 0 ) {
        foreach($prefixes as $p) {
          // remove from master set
          $pipe->srem($this->getIndexPrefix(), $p);

          // remove from the index
          $pipe->zrem("{$this->getIndexPrefix()}:{$p}", $item['id']);
        }
      }
    });
  }

  /**
   * Computes all the word prefixes for a given item which satisfy the
   * min-complete requirement and are not in the stop-words array.
   *
   * @param   array $item
   * @return  array
   */
  protected function prefixes($item) {
    return Str::prefixesForPhrase($this->itemPhrase($item), $this->getMinComplete(), $this->getStopWords());
  }

  /**
   * Computes the full-phrase for the given item taking into account its
   * term + aliases (if provided).
   *
   * @param   array $item
   * @return  string
   */
  protected function itemPhrase($item) {
    $phrase = [$item['term']];

    if ( isset($item['aliases']) )
      $phrase = array_merge($phrase, $item['aliases']);

    return implode(' ', $phrase);
  }

}
