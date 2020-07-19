<?php

namespace Facebook\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

class FbaamsettingsTest extends \PHPUnit\Framework\TestCase{

  protected $fbeHelper;

  protected $context;

  protected $resultJsonFactory;

  protected $fbaamsettings;

  protected $request;

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
    $this->context = $this->createMock(\Magento\Backend\App\Action\Context::class);
    $this->resultJsonFactory = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
    $this->fbeHelper = $this->createMock(\Facebook\BusinessExtension\Helper\FBEHelper::class);
    $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
    $this->context->method('getRequest')->willReturn($this->request);
    $this->fbaamsettings = new \Facebook\BusinessExtension\Controller\Adminhtml\Ajax\Fbaamsettings(
      $this->context,
      $this->resultJsonFactory,
      $this->fbeHelper
    );
  }

  private function setupRequestAndSettings($pixelId, $settings){
    $this->request->method('getParam')
                        ->willReturn($pixelId);
    $this->fbeHelper->method('fetchAAMSettings')->willReturn($settings);
  }

  /**
    * Test the success field in returned json is false when an invalid pixel id is sent
    *
    * @return void
  */
  public function testJsonNotSucessfullWhenInvalidPixelId() {
    $this->setupRequestAndSettings('1234', null);
    $result = $this->fbaamsettings->executeForJson();
    $this->assertFalse($result['success']);
    $this->assertNull($result['settings']);
  }

  /**
    * Test the success field in returned json is true when a valid pixel id is sent
    * and the response contains the json representation of the settings
    *
    * @return void
  */
  public function testJsonSucessfullWhenValidPixelId() {
    $pixelId = '1234';
    $settingsAsArray = array(
        "enableAutomaticMatching"=>false,
        "enabledAutomaticMatchingFields"=>['em'],
        "pixelId"=>$pixelId
      );
    $settingsAsString = json_encode($settingsAsArray);
    $settings = new AdsPixelSettings();
    $settings->setPixelId($settingsAsArray['pixelId']);
    $settings->setEnableAutomaticMatching($settingsAsArray['enableAutomaticMatching']);
    $settings->setEnabledAutomaticMatchingFields($settingsAsArray['enabledAutomaticMatchingFields']);
    $this->setupRequestAndSettings($pixelId, $settings);
    $result = $this->fbaamsettings->executeForJson();
    $this->assertTrue($result['success']);
    $this->assertEquals( $settingsAsString, $result['settings'] );
  }
}
