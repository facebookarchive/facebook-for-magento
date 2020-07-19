<?php

namespace Facebook\BusinessExtension\Test\Unit\Helper;

class FBEHelperTest extends \PHPUnit\Framework\TestCase{

  protected $fbeHelper;

  protected $context;

  protected $objectManagerInterface;

  protected $configFactory;

  protected $logger;

  protected $directorylist;

  protected $storeManager;

  protected $curl;

  protected $resourceConnection;

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

    $this->context = $this->createMock(\Magento\Framework\App\Helper\Context::class);
    $this->objectManagerInterface = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
    $this->configFactory = $this->createMock(\Facebook\BusinessExtension\Model\ConfigFactory::class);
    $this->logger = $this->createMock(\Facebook\BusinessExtension\Logger\Logger::class);
    $this->directorylist = $this->createMock(\Magento\Framework\App\Filesystem\DirectoryList::class);
    $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
    $this->curl = $this->createMock(\Magento\Framework\HTTP\Client\Curl::class);
    $this->resourceConnection = $this->createMock(\Magento\Framework\App\ResourceConnection::class);

    $this->fbeHelper = new \Facebook\BusinessExtension\Helper\FBEHelper(
      $this->context,
      $this->objectManagerInterface,
      $this->configFactory,
      $this->logger,
      $this->directorylist,
      $this->storeManager,
      $this->curl,
      $this->resourceConnection
    );
  }

  private function createRowWithValue( $configValue ){
    $configRow = $this->getMockBuilder(\Facebook\BusinessExtension\Model\Config::class)
                  ->disableOriginalConstructor()
                  ->setMethods(['getConfigValue', 'load'])
                  ->getMock();
    if($configValue == null){
      $configRow->method('load')->willReturn(null);
    }
    else{
      $configRow->method('load')->willReturn($configRow);
      $configRow->method('getConfigValue')->willReturn($configValue);
    }
    return $configRow;
  }

  /**
    * Test that the returned access token is null when there is no row in the database
    *
    * @return void
  */
  public function testAccessTokenNullWhenNotPresentInDb() {
    $configRow = $this->createRowWithValue(null);

    $this->configFactory->method('create')
                        ->willReturn($configRow);

    $this->assertNull( $this->fbeHelper->getAccessToken());
  }

  /**
    * Test that the returned access token is not null when there is a row in the database
    *
    * @return void
  */
  public function testAccessTokenNotNullWhenPresentInDb() {
    $dummyToken = '1234';
    $configRow = $this->createRowWithValue($dummyToken);

    $this->configFactory->method('create')
                        ->willReturn($configRow);

    $this->assertEquals($dummyToken, $this->fbeHelper->getAccessToken());
  }

  /**
    * Test that the returned aam settings are null when there is no row in the database
    *
    * @return void
  */
  public function testAAMSettingsNullWhenNotPresentInDb() {
    $configRow = $this->createRowWithValue(null);

    $this->configFactory->method('create')
                        ->willReturn($configRow);

    $this->assertNull($this->fbeHelper->getAAMSettings());
  }

  /**
    * Test that the returned aam settings are not null when there is a row in the database
    *
    * @return void
  */
  public function testAAMSettingsNotNullWhenPresentInDb() {
    $settingsAsArray = array(
        "enableAutomaticMatching"=>false,
        "enabledAutomaticMatchingFields"=>['em'],
        "pixelId"=>"1234"
      );
    $settingsAsString = json_encode($settingsAsArray);

    $configRow = $this->createRowWithValue($settingsAsString);

    $this->configFactory->method('create')
                        ->willReturn($configRow);

    $settings = $this->fbeHelper->getAAMSettings();

    $this->assertEquals($settings->getEnableAutomaticMatching(), $settingsAsArray['enableAutomaticMatching']);
    $this->assertEquals($settings->getEnabledAutomaticMatchingFields(), $settingsAsArray['enabledAutomaticMatchingFields']);
    $this->assertEquals($settings->getPixelId(), $settingsAsArray['pixelId']);
  }
}
