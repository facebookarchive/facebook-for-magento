<?php

namespace Facebook\BusinessExtension\Test\Unit\Cron;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

use \Facebook\BusinessExtension\Helper\FBEHelper;
use \Facebook\BusinessExtension\Cron\AAMSettingsCron;

class EventIdGeneratorTest extends \PHPUnit\Framework\TestCase{

  protected $aamSettingsCron;

  protected $fbeHelper;
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
    $this->aamSettingsCron = new \Facebook\BusinessExtension\Cron\AAMSettingsCron($this->fbeHelper);
  }

  /**
    * Test that the settings returned by the cron object are null when there is no pixel in the db
    *
    * @return void
  */
  public function testNullSettingsWhenNoPixelPresent() {
    $pixelId = null;

    $this->fbeHelper->method('getPixelID')->willReturn($pixelId);

    $result = $this->aamSettingsCron->execute();

    $this->assertNull($result);
  }

  /**
    * Test that the settings returned by the cron object are null when they cannot be fetched
    *
    * @return void
  */
  public function testNullSettingsWhenAAMSettingsNotFetched() {
    $pixelId = '1234';

    $this->fbeHelper->method('getPixelID')->willReturn($pixelId);
    $this->fbeHelper->method('fetchAndSaveAAMSettings')->willReturn(null);

    $result = $this->aamSettingsCron->execute();

    $this->assertNull($result);
  }

  /**
    * Test that the settings returned by the cron object are not null when pixel and aam settings are valid
    *
    * @return void
  */
 public function testSettingsNotNullWhenPixelAndAAMSettingsAreValid(){
    $pixelId = '1234';
    $settingsAsArray = array(
        "enableAutomaticMatching"=>false,
        "enabledAutomaticMatchingFields"=>['em'],
        "pixelId"=>$pixelId
      );
    $settingsAsString = json_encode($settingsAsArray);

    $this->fbeHelper->method('getPixelID')->willReturn($pixelId);
    $this->fbeHelper->method('fetchAndSaveAAMSettings')->willReturn($settingsAsString);

    $result = $this->aamSettingsCron->execute();

    $this->assertEquals($settingsAsString, $result);
 }
}
