<?php

namespace Facebook\BusinessExtension\Test\Unit\Observer;

class ViewCategoryTest extends CommonTest{

  protected $registry;

  protected $viewCategoryObserver;

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
    $this->viewCategoryObserver = new \Facebook\BusinessExtension\Observer\ViewCategory( $this->fbeHelper, $this->serverSideHelper, $this->registry );
  }

  public function testViewCategoryEventNotCreatedWhenS2SDisabled(){
    $this->fbeHelper->method('isS2SEnabled')->willReturn(false);
    $observer = new \Magento\Framework\Event\Observer(['eventId' => '1234']);
    $this->assertEquals($this->viewCategoryObserver->execute($observer), $this->viewCategoryObserver);
    $this->assertEquals(0, count($this->serverSideHelper->getTrackedEvents()));
  }

  public function testViewCategoryEventCreatedWhenS2SEnabled(){
    $this->fbeHelper->method('isS2SEnabled')->willReturn(true);

    $category = $this->objectManager->getObject('Magento\Catalog\Model\Category');
    $category->setName('Electronics');
    $this->registry->method('registry')->willReturn($category);

    $observer = new \Magento\Framework\Event\Observer(['eventId' => '1234']);

    $this->viewCategoryObserver->execute($observer);

    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

    $event = $this->serverSideHelper->getTrackedEvents()[0];

    $this->assertEquals('1234', $event->getEventId());

    $customDataArray = array(
      'content_category' => 'Electronics'
    );

    $this->assertEqualsCustomData($customDataArray, $event->getCustomData());
  }
}
