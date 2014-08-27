<?php
namespace Compleet\Helpers;

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

  /**
   * Builds an array with the range of values provided filled in.
   *
   * @param int   start
   * @param int   end
   * @param bool  inclusive
   */
  public static function range($start, $end, $inclusive = true) {
    $output = [];

    $max = $inclusive ? $end : $end - 1;

    if ( $start >= $max ) return $output;

    for ($value = $start; $value <= $max; $value++)
      $output[] = $value;

    return $output;
  }

}
