<?php
namespace Compleet\Helpers;

class Str {

  public static function size($input) {
    return grapheme_strlen($input);
  }

  public static function normalize($input) {
    $output = mb_strtolower($input, 'UTF-8');
    $output = preg_replace('/[^\p{L}\p{N}\ ]/ui', '', $output);
    $output = preg_replace('/\s+$/u', '', $output);
    $output = preg_replace('/^\s+/u', '', $output);

    return $output;
  }

  public static function prefixesForPhrase($phrase, $minComplete = 2, $stopWords = []) {
    $words = array_filter(explode(' ', static::normalize($phrase)), function($w) use ($stopWords) {
      return !in_array($w, $stopWords);
    });

    $prefixes = array_map(function($w) use ($minComplete) {
      return array_map(function($l) use ($w) {
        return grapheme_substr($w, 0, $l + 1);
      }, range($minComplete - 1, static::size($w) - 1));
    } , $words);

    return array_unique(Ary::flatten($prefixes));
  }

}
