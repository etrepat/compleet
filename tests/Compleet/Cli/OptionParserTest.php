<?php
namespace Compleet\Cli;

class OptionParserTest extends \PHPUnit_Framework_TestCase {

  protected $parser = null;

  public function setUp() {
    $this->parser = new OptionParser;
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * @expectedExceptionMessage ! Unknown option: -j
   */
  public function testRaisesExceptionWhenNoOptionsDefined() {
    $args = ['', '-j', 'somefile'];

    $this->parser->parse($args);
  }

  public function testAcceptsOnlyArgumentsWhenNoOptionsDefined() {
    $args = ['', 'somearg'];

    $options = $this->parser->parse($args);

    $this->assertEmpty($options);
    $this->assertEquals(1, count($args));
    $this->assertEquals('somearg', $args[0]);

    $args = ['', 'somearg', 'someotherarg', 'yetanotherarg'];

    $options = $this->parser->parse($args);
    $this->assertEmpty($options);
    $this->assertEquals(3, count($args));
    $this->assertEquals('yetanotherarg', $args[2]);
    $this->assertEquals(['somearg', 'someotherarg', 'yetanotherarg'], $args);
  }

  public function testSimpleShortBooleanOptions() {
    $this->parser->on('-h', null, OptionParser::OPTION_VALUE_NONE, '');

    $args = ['', '-h'];
    $options = $this->parser->parse($args);

    $this->assertTrue($options['h']);
    $this->assertEmpty($args);
  }

  public function testSimpleShortOptionsWithArgument() {
    $this->parser->on('-c', null, OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '-c', 'someconfigfile'];
    $options = $this->parser->parse($args);

    $this->assertEquals('someconfigfile', $options['c']);
    $this->assertEmpty($args);
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * ! Option '-c' requires a parameter
   */
  public function testSimpleShortOptionsWithArgumentFailsIfNotProvided() {
    $this->parser->on('-c', null, OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '-c'];
    $options = $this->parser->parse($args);
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * ! Option '-c' requires a parameter
   */
  public function testSimpleShortOptionsWithArgumentFailsIfNotProvided2() {
    $this->parser
      ->on('-c', null, OptionParser::OPTION_VALUE_REQUIRED, '')
      ->on('-j', null, OptionParser::OPTION_VALUE_NONE, '');

    $args = ['', '-c', '-j'];
    $options = $this->parser->parse($args);
  }

  public function testSimpleLongBooleanOptions() {
    $this->parser->on('--toggle', null, OptionParser::OPTION_VALUE_NONE, '');

    $args = ['', '--toggle'];
    $options = $this->parser->parse($args);

    $this->assertTrue($options['toggle']);
    $this->assertEmpty($args);
  }

  public function testSimpleLongOptionsWithArgument() {
    $this->parser->on('--switch-to', null, OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '--switch-to', 'someother'];
    $options = $this->parser->parse($args);

    $this->assertEquals('someother', $options['switch-to']);
    $this->assertEmpty($args);
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * ! Option '--config' requires a parameter
   */
  public function testSimpleLongOptionsWithArgumentFailsIfNotProvided() {
    $this->parser->on('--config', null, OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '--config'];
    $options = $this->parser->parse($args);
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * ! Option '--config' requires a parameter
   */
  public function testSimpleLongOptionsWithArgumentFailsIfNotProvided2() {
    $this->parser
      ->on('--config', null, OptionParser::OPTION_VALUE_REQUIRED, '')
      ->on('--toogle', null, OptionParser::OPTION_VALUE_NONE, '');

    $args = ['', '--config', '--toggle'];
    $options = $this->parser->parse($args);
  }

  public function testSimpleAliasedBooleanOptions() {
    $this->parser->on('-t', '--toggle', OptionParser::OPTION_VALUE_NONE, '');

    $args = ['', '-t'];
    $options = $this->parser->parse($args);

    $this->assertTrue($options['toggle']);
    $this->assertEmpty($args);

    $options = null;

    $args = ['', '--toggle'];
    $options = $this->parser->parse($args);

    $this->assertTrue($options['toggle']);
    $this->assertEmpty($args);
  }

  public function testSimpleAliasedOptionsWithArgument() {
    $this->parser->on('-c', '--config', OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '-c', 'path/to/config'];
    $options = $this->parser->parse($args);

    $this->assertEquals('path/to/config', $options['config']);
    $this->assertEmpty($args);

    $options = null;

    $args = ['', '--config', 'path/to/config'];
    $options = $this->parser->parse($args);

    $this->assertEquals('path/to/config', $options['config']);
    $this->assertEmpty($args);
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * ! Option '--config' requires a parameter
   */
  public function testSimpleAliasedOptionsWithArgumentFailsIfNotProvided() {
    $this->parser->on('-c', '--config', OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '--config'];
    $options = $this->parser->parse($args);
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * ! Option '--config' requires a parameter
   */
  public function testSimpleAliasedOptionsWithArgumentFailsIfNotProvided2() {
    $this->parser->on('-c', '--config', OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '-c'];
    $options = $this->parser->parse($args);
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * ! Option '--config' requires a parameter
   */
  public function testSimpleAliasedOptionsWithArgumentFailsIfNotProvided3() {
    $this->parser
      ->on('-c', '--config', OptionParser::OPTION_VALUE_REQUIRED, '')
      ->on('-t', '--toogle', OptionParser::OPTION_VALUE_NONE, '');

    $args = ['', '--config', '--toggle'];
    $options = $this->parser->parse($args);
  }

  /**
   * @expectedException Compleet\Cli\OptionParserException
   * ! Option '--config' requires a parameter
   */
  public function testSimpleAliasedOptionsWithArgumentFailsIfNotProvided4() {
    $this->parser
      ->on('-c', '--config', OptionParser::OPTION_VALUE_REQUIRED, '')
      ->on('-t', '--toogle', OptionParser::OPTION_VALUE_NONE, '');

    $args = ['', '-c', '--toggle'];
    $options = $this->parser->parse($args);
  }

  public function testParsingExtractsOptionsAndLeavesArgumentsOnly() {
    $this->parser
      ->on('-a', null, OptionParser::OPTION_VALUE_NONE, '')
      ->on('-b', null, OptionParser::OPTION_VALUE_REQUIRED, '')
      ->on('-c', null, OptionParser::OPTION_VALUE_NONE, '')
      ->on('-d', null, OptionParser::OPTION_VALUE_REQUIRED, '')
      ->on('-e', null, OptionParser::OPTION_VALUE_NONE, '')
      ->on('-f', null, OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '-a', '-b', 'some', '-c', '-d', 'somemore', 'arg1', 'arg2', '-e', '-f', 'somemoremore'];
    $options = $this->parser->parse($args);

    $this->assertTrue($options['a']);
    $this->assertEquals('some', $options['b']);
    $this->assertTrue($options['c']);
    $this->assertEquals('somemore', $options['d']);
    $this->assertTrue($options['e']);
    $this->assertEquals('somemoremore', $options['f']);

    $this->assertEquals(2, count($args));
    $this->assertEquals('arg1', $args[0]);
  }

  public function testOptionArgumentsSupportEqual() {
    $this->parser
      ->on('-c', '--config', OptionParser::OPTION_VALUE_REQUIRED, '');

    $args = ['', '-c=some/path', 'command'];
    $options = $this->parser->parse($args);

    $this->assertEquals('some/path', $options['config']);
    $this->assertEquals(1, count($args));
    $this->assertEquals('command', $args[0]);

    $options = null;

    $args = ['', '--config=some/path', 'command'];
    $options = $this->parser->parse($args);

    $this->assertEquals('some/path', $options['config']);
    $this->assertEquals(1, count($args));
    $this->assertEquals('command', $args[0]);
  }

}
