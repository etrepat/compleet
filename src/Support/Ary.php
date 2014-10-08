<?php
namespace Compleet\Support;

class Ary {

  /**
   * Flatten a multi-dimensional array into a single level.
   *
   * @param  array  $ary
   * @return array
   */
  public static function flatten($ary) {
    $output = [];

    array_walk_recursive($ary, function($v) use (&$output) { $output[] = $v; });

    return $output;
  }

}
