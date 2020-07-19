<?php

namespace Facebook\BusinessExtension\Test\Unit\Observer;

class SearchTest extends CommonTest{

  protected $request;

  protected $searchObserver;

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
    parent::setUp();
    $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
    $this->searchObserver = new \Facebook\BusinessExtension\Observer\Search( $this->fbeHelper, $this->serverSideHelper, $this->request );
  }

  public function testSearchEventNotCreatedWhenS2SDisabled(){
    $this->fbeHelper->method('isS2SEnabled')->willReturn(false);
    $observer = new \Magento\Framework\Event\Observer(['eventId' => '1234']);
    $this->assertEquals($this->searchObserver->execute($observer), $this->searchObserver);
    $this->assertEquals(0, count($this->serverSideHelper->getTrackedEvents()));
  }

  public function testSearchEventCreatedWhenS2SEnabled(){
    $this->fbeHelper->method('isS2SEnabled')->willReturn(true);

    $this->request->method('getParam')->willReturn('Door');

    $observer = new \Magento\Framework\Event\Observer(['eventId' => '1234']);

    $this->searchObserver->execute($observer);

    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

    $event = $this->serverSideHelper->getTrackedEvents()[0];

    $this->assertEquals('1234', $event->getEventId());

    $customDataArray = array(
      'search_string' => 'Door'
    );

    $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
  }
}
