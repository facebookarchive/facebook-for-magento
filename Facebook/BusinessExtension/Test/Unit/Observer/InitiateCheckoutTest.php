<?php

namespace Facebook\BusinessExtension\Test\Unit\Observer;

class InitiateCheckoutTest extends CommonTest{

  protected $initiateCheckoutObserver;

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
    $this->initiateCheckoutObserver = new \Facebook\BusinessExtension\Observer\InitiateCheckout($this->fbeHelper, $this->magentoDataHelper, $this->serverSideHelper);
  }

  public function testInitiateCheckoutEventCreated(){
    $this->magentoDataHelper->method('getCartTotal')->willReturn(170);
    $this->magentoDataHelper->method('getCartContentIds')->willReturn(
      array(1, 2)
    );
    $this->magentoDataHelper->method('getCartContents')->willReturn(
      array(
        array( 'product_id'=>1, 'quantity'=>1, 'item_price' =>20 ),
        array( 'product_id'=>2, 'quantity'=>3, 'item_price' =>50 )
      )
    );
    $this->magentoDataHelper->method('getCartNumItems')->willReturn(4);

    $observer = new \Magento\Framework\Event\Observer(['eventId' => '1234']);

    $this->initiateCheckoutObserver->execute($observer);

    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

    $event = $this->serverSideHelper->getTrackedEvents()[0];

    $this->assertEquals('1234', $event->getEventId());

    $customDataArray = array(
      'currency' => 'USD',
      'value' => 170,
      'content_type' => 'product',
      'content_ids' => array(1, 2),
      'contents' => array(
        array( 'product_id'=>1, 'quantity'=>1, 'item_price' =>20 ),
        array( 'product_id'=>2, 'quantity'=>3, 'item_price' =>50 )
      ),
      'num_items' => 4
    );

    $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
  }
}
