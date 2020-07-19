<?php

namespace Facebook\BusinessExtension\Test\Unit\Observer;

abstract class CommonTest extends \PHPUnit\Framework\TestCase{

  protected $magentoDataHelper;

  protected $fbeHelper;

  protected $objectManager;

  protected $serverSideHelper;

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
    $this->fbeHelper = $this->createMock(\Facebook\BusinessExtension\Helper\FBEHelper::class);
    $this->magentoDataHelper = $this->createMock(\Facebook\BusinessExtension\Helper\MagentoDataHelper::class);
    $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    $this->serverSideHelper = new \Facebook\BusinessExtension\Helper\ServerSideHelper($this->fbeHelper, $this->magentoDataHelper);
    $this->fbeHelper->method('getAccessToken')->willReturn('');
    $this->fbeHelper->method('getPixelId')->willReturn('123');
    $this->magentoDataHelper->method('getCurrency')->willReturn('USD');
  }

  public function assertEqualsCustomData($customDataArray, $customData){
    if (!empty($customDataArray['currency'])) {
      $this->assertEquals($customData->getCurrency(), $customDataArray['currency']);
    }

    if (!empty($customDataArray['value'])) {
      $this->assertEquals($customData->getValue(), $customDataArray['value']);
    }

    if (!empty($customDataArray['content_ids'])) {
      $this->assertEquals($customData->getContentIds(), $customDataArray['content_ids']);
    }

    if (!empty($customDataArray['content_type'])) {
      $this->assertEquals($customData->getContentType(), $customDataArray['content_type']);
    }

    if (!empty($customDataArray['content_name'])) {
      $this->assertEquals($customData->getContentName(), $customDataArray['content_name']);
    }

    if (!empty($customDataArray['content_category'])) {
      $this->assertEquals($customData->getContentCategory(), $customDataArray['content_category']);
    }

    if (!empty($customDataArray['search_string'])) {
      $this->assertEquals($customData->getSearchString(), $customDataArray['search_string']);
    }

    if (!empty($customDataArray['num_items'])) {
      $this->assertEquals($customData->getNumItems(), $customDataArray['num_items']);
    }

    if( !empty($customDataArray['order_id']) ){
      $this->assertEquals($customData->getOrderId(), $customDataArray['order_id']);
    }

    if (!empty($customDataArray['contents'])) {
      $contents = $customData->getContents();
      $this->assertNotNull($contents);
      $this->assertEquals(count($customDataArray['contents']), count($contents));
      for( $i = 0; $i < count($contents); $i ++ ){
        if( !empty($customDataArray['contents'][$i]['product_id']) ){
          $this->assertEquals($customDataArray['contents'][$i]['product_id'], $contents[$i]->getProductId());
        }
        if(!empty($customDataArray['contents'][$i]['quantity'])){
          $this->assertEquals($customDataArray['contents'][$i]['quantity'], $contents[$i]->getQuantity());
        }
        if(!empty($customDataArray['contents'][$i]['item_price'])){
          $this->assertEquals($customDataArray['contents'][$i]['item_price'], $contents[$i]->getItemPrice());
        }
      }
    }
  }
}
