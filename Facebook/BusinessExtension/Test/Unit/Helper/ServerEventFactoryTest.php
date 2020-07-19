<?php

namespace Facebook\BusinessExtension\Test\Unit\Helper;

use \Facebook\BusinessExtension\Helper\ServerEventFactory;

class ServerEventFactoryTest extends \PHPUnit\Framework\TestCase{

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

  public function testNewEventHasId(){
    $event = ServerEventFactory::newEvent('ViewContent');
    $this->assertNotNull($event->getEventId());
  }

  public function testNewEventHasProvidedId(){
    $eventId = '1234';
    $event = ServerEventFactory::newEvent('ViewContent', $eventId);
    $this->assertEquals($event->getEventId(), $eventId);
  }

  public function testNewEventHasEventTime() {
    $event = ServerEventFactory::newEvent('ViewContent');
    $this->assertNotNull($event->getEventTime());
    $this->assertLessThan(1, time() - $event->getEventTime());
  }

  public function testNewEventHasEventName() {
    $event =  ServerEventFactory::newEvent('ViewContent');
    $this->assertEquals('ViewContent', $event->getEventName());
  }

  public function testNewEventHasIpAddress(){
    $_SERVER['HTTP_CLIENT_IP'] = '192.168.0.1';
    $event =  ServerEventFactory::newEvent('ViewContent');
    $this->assertEquals($event->getUserData()->getClientIpAddress(), '192.168.0.1');
  }

  public function testNewEventHasUserAgent() {
    $_SERVER['HTTP_USER_AGENT'] = 'test-agent';

    $event =  ServerEventFactory::newEvent('ViewContent');

    $this->assertEquals( $event->getUserData()->getClientUserAgent(), 'test-agent' );
  }

  public function testNewEventHasEventSourceUrlWithHttps() {
    $_SERVER['HTTPS'] = 'anyvalue';
    $_SERVER['HTTP_HOST'] = 'www.pikachu.com';
    $_SERVER['REQUEST_URI'] = '/index.php';

    $event = ServerEventFactory::newEvent('ViewContent');

    $this->assertEquals('https://www.pikachu.com/index.php', $event->getEventSourceUrl());
  }

  public function testNewEventHasEventSourceUrlWithHttp() {
    $_SERVER['HTTPS'] = '';
    $_SERVER['HTTP_HOST'] = 'www.pikachu.com';
    $_SERVER['REQUEST_URI'] = '/index.php';

    $event = ServerEventFactory::newEvent('ViewContent');

    $this->assertEquals('http://www.pikachu.com/index.php', $event->getEventSourceUrl());
  }

  public function testNewEventHasFbc() {
    $_COOKIE['_fbc'] = '_fbc_value';

    $event = ServerEventFactory::newEvent('ViewContent');

    $this->assertEquals('_fbc_value', $event->getUserData()->getFbc());
  }

  public function testNewEventHasFbp() {
    $_COOKIE['_fbp'] = '_fbp_value';

    $event = ServerEventFactory::newEvent('ViewContent');

    $this->assertEquals('_fbp_value', $event->getUserData()->getFbp());
  }
}
