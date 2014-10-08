<?php
namespace Compleet\Support;

use Compleet\Support\Str;

class StrTestCase extends \PHPUnit_Framework_TestCase {

  public function testSize() {
    $this->assertEquals(9, Str::size('something'));
    $this->assertEquals(9, Str::size('sómèthïng'));
    $this->assertEquals(4, Str::size('测试中文'));
  }

  public function testSubstr() {
    $str = 'Hello World';

    $this->assertEquals('Hello World', Str::substr($str, 0));
    $this->assertEquals('World', Str::substr($str, 6));
    $this->assertEquals('ello', Str::substr($str, 1, 4));

    $str = '测试中文';
    $this->assertEquals('测试中文', Str::substr($str, 0));
    $this->assertEquals('中', Str::substr($str, 2, 1));
    $this->assertEquals('测试', Str::substr($str, 0, 2));
  }

  public function testNormalize() {
    $this->assertEquals('the knicks', Str::normalize('the.- knicks'));
    $this->assertEquals('', Str::normalize(',\'`~!@#$%^&*()_-{}\'"?/|\\'));
    $this->assertEquals('测试中文', Str::normalize('测试中文'));
    $this->assertEquals('测试中文', Str::normalize('"·||..?¿测试中文?¿!!!'));
  }

  public function testPrefixesForPhrase() {
    $min = 2;
    $swords = ['the'];

    $this->assertEquals(['kn', 'kni', 'knic', 'knick', 'knicks'], Str::prefixesForPhrase('the knicks', $min, $swords));
    $this->assertEquals(['te', 'tes', 'test', 'testi', 'testin', 'th', 'thi', 'this'], Str::prefixesForPhrase("testin' this", $min, $swords));
    $this->assertEquals(['te', 'tes', 'test', 'testi', 'testin', 'th', 'thi', 'this'], Str::prefixesForPhrase("testin' this", $min, $swords));
    $this->assertEquals(['te', 'tes', 'test'], Str::prefixesForPhrase('test test', $min, $swords));
    $this->assertEquals(['so', 'sou', 'soul', 'soulm', 'soulma', 'soulmat', 'soulmate'], Str::prefixesForPhrase('SoUlmATE', $min, $swords));
    $this->assertEquals(['测试', '测试中', '测试中文', 'te', 'tes', 'test'], Str::prefixesForPhrase('测试中文 test', $min, $swords));

    $min = 4;
    $this->assertEquals(['同华东生', '同华东生产', '同华东生产队', 'abcd', 'abcde'], Str::prefixesForPhrase('同华东生产队 abcde', $min, $swords));
  }

}
