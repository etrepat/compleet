<?php
namespace Compleet;

use Compleet\Helpers\Str;

class StrTestCase extends \PHPUnit_Framework_TestCase {

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
