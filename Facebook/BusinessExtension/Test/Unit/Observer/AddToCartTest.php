<?php

namespace Facebook\BusinessExtension\Test\Unit\Observer;

class AddToCartTest extends CommonTest{

  protected $request;

  protected $addToCartObserver;

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
    $this->addToCartObserver = new \Facebook\BusinessExtension\Observer\AddToCart( $this->fbeHelper, $this->magentoDataHelper, $this->serverSideHelper, $this->request );
  }

  public function testAddToCartEventCreated(){
    $this->magentoDataHelper->method('getValueForProduct')->willReturn(12.99);
    $this->magentoDataHelper->method('getCategoriesForProduct')->willReturn('Electronics');
    $product = $this->objectManager->getObject( '\Magento\Catalog\Model\Product' );
    $product->setId(123);
    $product->setName('Earphones');
    $this->request->method('getParam')->willReturn('123');
    $this->magentoDataHelper->method('getProductWithSku')->willReturn($product);

    $observer = new \Magento\Framework\Event\Observer(['eventId' => '1234']);

    $this->addToCartObserver->execute($observer);

    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

    $event = $this->serverSideHelper->getTrackedEvents()[0];

    $this->assertEquals('1234', $event->getEventId());

    $customDataArray = array(
      'currency' => 'USD',
      'value' => 12.99,
      'content_type' => 'product',
      'content_ids' => array(123),
      'content_category' => 'Electronics',
      'content_name' => 'Earphones'
    );

    $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
  }
}
