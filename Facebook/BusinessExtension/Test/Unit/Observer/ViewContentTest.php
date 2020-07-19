<?php

namespace Facebook\BusinessExtension\Test\Unit\Observer;

class ViewContentTest extends CommonTest{

  protected $registry;

  protected $viewContentObserver;

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
    $this->registry = $this->createMock(\Magento\Framework\Registry::class);
    $this->viewContentObserver = new \Facebook\BusinessExtension\Observer\ViewContent( $this->fbeHelper, $this->serverSideHelper, $this->magentoDataHelper, $this->registry );
  }

  public function testViewContentEventNotCreatedWhenS2SDisabled(){
    $this->fbeHelper->method('isS2SEnabled')->willReturn(false);
    $observer = new \Magento\Framework\Event\Observer(['eventId' => '1234']);
    $this->assertEquals($this->viewContentObserver->execute($observer), $this->viewContentObserver);
    $this->assertEquals(0, count($this->serverSideHelper->getTrackedEvents()));
  }

  public function testViewContentEventCreatedWhenS2SEnabled(){
    $this->fbeHelper->method('isS2SEnabled')->willReturn(true);

    $this->magentoDataHelper->method('getValueForProduct')->willReturn(12.99);
    $this->magentoDataHelper->method('getCategoriesForProduct')->willReturn('Electronics');
    $product = $this->objectManager->getObject( '\Magento\Catalog\Model\Product' );
    $product->setId(123);
    $product->setName('Earphones');
    $this->registry->method('registry')->willReturn($product);

    $observer = new \Magento\Framework\Event\Observer(['eventId' => '1234']);

    $this->viewContentObserver->execute($observer);

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
