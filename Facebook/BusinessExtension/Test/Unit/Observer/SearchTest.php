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

  public function testSearchEventCreated(){
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
