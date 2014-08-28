<?php
namespace Compleet\Cli;

class OptionParser {

  const OPTION_VALUE_NONE     = 'option-value-none';

  const OPTION_VALUE_REQUIRED = 'option-value-required';

  protected $banner = '';

  protected $specs = [];

  protected $help = ['Options:'];

  public function banner($banner) {
    $this->banner = $banner;

    return $this;
  }

  public function separator($text) {
    $this->help[] = $text;

    return $this;
  }

  public function on($short, $long = null, $type = self::OPTION_VALUE_NONE, $description = '') {
    $spec = [[$short, $long], $type, $description];

    $this->specs[] = $spec;

    $this->help[] = $this->specToText($spec);

    return $this;
  }

  public function parse(&$args) {
    $options = [];
    $other = [];

    array_shift($args);

    if ( empty($args) || !is_array($args) ) return $options;

    while ( ($opt = array_shift($args)) !== NULL ) {
      if ( $opt[0] == '-' ) {
        if ( strpos($opt, '=') !== false ) {
          list($opt, $arg) = explode('=', $opt);

          array_unshift($args, $arg);
        }

        $sidx = $this->specIndexOf($opt);

        if ( $sidx != -1 ) {
          $spec = $this->specs[$sidx];

          $key = $this->specKey($spec);

          $options[$key] = null;

          switch ( $spec[1] ) {
            case self::OPTION_VALUE_REQUIRED:
              $value = array_shift($args);

              if ( $value[0] != '-' )
                $options[$key] = $value;
              else
                array_unshift($args, $value);
              break;

            case self::OPTION_VALUE_NONE:
            default:
              $options[$key] = true;
              break;
          }
        } else {
          throw new OptionParserException("! Unknown option: {$opt}");
        }
      } else {
        $other[] = $opt;
      }
    }

    foreach($options as $opt => $value)
      if ( is_null($value) )
        throw new OptionParserException("! Option '{$opt}' requires a parameter");

    $args = $other;

    return $options;
  }

  public function help() {
    $lines = array_merge([$this->banner], $this->help);

    return implode(PHP_EOL, $lines);
  }

  public function __toString() {
    return $this->help();
  }

  protected function specIndexOf($option) {
    foreach($this->specs as $index => $data) {
      $switch = $data[0];

      if ( $option == $switch[0] || (!is_null($switch[1]) && $option == $switch[1]) )
        return $index;
    }

    return -1;
  }

  protected function specToText(array $spec) {
    $switch = implode(', ', $spec[0]);
    return "\r    $switch\r\t\t\t{$spec[2]}";
  }

  protected function specKey(array $spec) {
    list($short, $long) = $spec[0];
    $key = is_null($long) ? $short : $long;

    return preg_replace('/^\-\-?/i', '', $key);
  }

}
