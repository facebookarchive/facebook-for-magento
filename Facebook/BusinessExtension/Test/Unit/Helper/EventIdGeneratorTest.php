<?php

namespace Facebook\BusinessExtension\Test\Unit\Helper;

use \Facebook\BusinessExtension\Helper\EventIdGenerator;

class EventIdGeneratorTest extends \PHPUnit\Framework\TestCase{
  /**
    * Used to reset or change values after running a test
    *
    * @return void
  */
  public function tearDown() {
  }

  /**
    * Used to set the values before running a test
    *
    * @return void
  */
  public function setUp() {
  }

  /**
    * Test generation of unique event ids
    *
    * @return void
  */
  public function testGeneratesUniqueValues() {
    $eventIds = array();
    for ($i = 0; $i < 100; $i++) {
      $eventIds[] = EventIdGenerator::guidv4();
    }
    $eventIds = array_unique($eventIds);
    $this->assertEquals(100, count($eventIds));
  }
}
