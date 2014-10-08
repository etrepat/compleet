<?php
namespace Compleet\Support;

use Compleet\Support\Ary;

class AryTestCase extends \PHPUnit_Framework_TestCase {

  public function testFlatten() {
    $this->assertEquals(['a','b','c','d','e'], Ary::flatten([['a', ['b', ['c']]], 'd', ['e']]));
    $this->assertEquals([1,2,3,4,5], Ary::flatten([[1,[2,3],[[4,[5]]]]]));
  }

}
