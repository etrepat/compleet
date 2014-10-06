<?php
namespace Compleet\Cli;

class OptionParser {

  /**
   * Option switch with no value identifier.
   *
   * @var string
   */
  const OPTION_VALUE_NONE     = 'option-value-none';

  /**
   * Option switch with a required value identifier.
   *
   * @var string
   */
  const OPTION_VALUE_REQUIRED = 'option-value-required';

  /**
   * Banner message.
   *
   * @var string
   */
  protected $banner = '';

  /**
   * Option definitions (specs).
   *
   * @var array
   */
  protected $specs = [];

  /**
   * Translated option definitions for help message output.
   *
   * @var array
   */
  protected $help = ['', 'Options:'];

  /**
   * Sets the banner message.
   *
   * @param   string $banner
   * @return  Compleet\Cli\OptionParser
   */
  public function banner($banner) {
    $this->banner = $banner;

    return $this;
  }

  /**
   * Sets a separator help message.
   *
   * @param   string $text
   * @return  Compleet\Cli\OptionParser
   */
  public function separator($text) {
    $this->help[] = $text;

    return $this;
  }

  /**
   * Registers a new spec/option definition.
   *
   * Sample usage:
   *    $parser = new Compleet\Cli\OptionParser;
   *    $parser->banner("Usage:\n    awesome-script [OPTIONS] COMMAND")
   *      ->separator('')
   *      ->on('-s', '--server'     , OptionParser::OPTION_VALUE_REQUIRED , 'Server connection string')
   *      ->on('-v', '--version'    , OptionParser::OPTION_VALUE_NONE     , 'Display version number')
   *      ->on('-h', '--help'       , OptionParser::OPTION_VALUE_NONE     , 'Show this help screen')
   *      ->separator('');
   *
   * @param   string  $short
   * @param   string  $long
   * @param   string  $type
   * @param   string  $description
   * @return  Compleet\Cli\OptionParser
   */
  public function on($short, $long = null, $type = self::OPTION_VALUE_NONE, $description = '') {
    $spec = [[$short, $long], $type, $description];

    $this->specs[] = $spec;

    $this->help[] = $this->specToText($spec);

    return $this;
  }

  /**
   * Parses the supplied command line arguments array and returns an array list
   * containing the identified option flags. All non-recognized arguments are
   * left in the array.
   *
   * @param   array $args
   * @return  array
   */
  public function parse(&$args) {
    $options = [];
    $other = [];

    array_shift($args);

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

  /**
   * Returns the help message.
   *
   * @return string
   */
  public function help() {
    $lines = array_merge([$this->banner], $this->help);

    return implode(PHP_EOL, $lines);
  }

  /**
   * __toString magic method. Aliased to `help()`.
   *
   * @return string
   */
  public function __toString() {
    return $this->help();
  }

  /**
   * Given an option switch, returns its index in the spec definition array or
   * -1 if not found.
   *
   * @param   string  $option
   * @return  mixed
   */
  protected function specIndexOf($option) {
    foreach($this->specs as $index => $data) {
      $switch = $data[0];

      if ( $option == $switch[0] || (!is_null($switch[1]) && $option == $switch[1]) )
        return $index;
    }

    return -1;
  }

  /**
   * Converts a single spec definition into a suitable format for printing into
   * the screen.
   *
   * @param   array $spec
   * @return  string
   */
  protected function specToText(array $spec) {
    $switch = implode(', ', $spec[0]);
    return "\r    $switch\r\t\t\t{$spec[2]}";
  }

  /**
   * Givin a spec definition, returns its key. A key is either the long version
   * of the option switches (if both are supplied) or the short one.
   *
   * @param   array $spec
   * @return  string
   */
  protected function specKey(array $spec) {
    list($short, $long) = $spec[0];
    $key = is_null($long) ? $short : $long;

    return preg_replace('/^\-\-?/i', '', $key);
  }

}
