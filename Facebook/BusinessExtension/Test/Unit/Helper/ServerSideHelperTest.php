<?php

namespace Facebook\BusinessExtension\Test\Unit\Helper;

use \Facebook\BusinessExtension\Helper\ServerEventFactory;
use \Facebook\BusinessExtension\Helper\AAMSettingsFields;

use \FacebookAds\Object\ServerSide\AdsPixelSettings;


class ServerSideHelperTest extends \PHPUnit\Framework\TestCase{

  protected $magentoDataHelper;

  protected $fbeHelper;

  protected $serverSideHelper;

  protected $objectManager;

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
    $this->serverSideHelper = new \Facebook\BusinessExtension\Helper\ServerSideHelper($this->fbeHelper, $this->magentoDataHelper);
    $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    $this->fbeHelper->method('getAccessToken')->willReturn('abc');
    $this->fbeHelper->method('getPixelID')->willReturn('123');
  }

  public function createDummyCustomer(){
    $customer = $this->objectManager->getObject( '\Magento\Customer\Model\Customer' );
    $customer->setEmail('abc@mail.com');
    $customer->setFirstname('Pedro');
    $customer->setLastname('Perez');
    $customer->setDob('2010-06-11');

    $this->magentoDataHelper->method('getCurrentCustomer')->willReturn($customer);

    return $customer;
  }

  public function createDummyAddress(){
    $address = $this->objectManager->getObject( '\Magento\Customer\Model\Address' );
    $address->setCity('Seattle');
    $address->setPostcode('98109');
    $address->setCountryId('US');
    $address->setTelephone('567891234');

    $this->magentoDataHelper->method('getCustomerAddress')->willReturn($address);

    return $address;
  }

  public function createDummyRegionCode(){
    $code = 'WA';
    $this->magentoDataHelper->method('getRegionCodeForAddress')->willReturn($code);
    return $code;
  }

  public function createDummyGender(){
    $gender = 'Male';
    $this->magentoDataHelper->method('getGenderAsString')->willReturn($gender);
    return $gender;
  }

  private function assertUserDataNull($userData){
    $this->assertNull($userData->getEmail());
    $this->assertNull($userData->getGender());
    $this->assertNull($userData->getFirstName());
    $this->assertNull($userData->getLastName());
    $this->assertNull($userData->getDateOfBirth());

    $this->assertNull($userData->getCity());
    $this->assertNull($userData->getZipCode());
    $this->assertNull($userData->getCountryCode());
    $this->assertNull($userData->getState());
    $this->assertNull($userData->getPhone());
  }

  private function assertEqualUserDataFromCustomer($customer, $userData){
    $this->assertEquals($userData->getEmail(), $customer->getEmail());
    $this->assertEquals($userData->getFirstName(), $customer->getFirstname());
    $this->assertEquals($userData->getLastName(), $customer->getLastname());
    $this->assertEquals($userData->getDateOfBirth(), date("Ymd", strtotime($customer->getDob())) );
  }

  private function assertEqualUserDataFromAddress($address, $userData){
    $this->assertEquals($userData->getCity(), $address->getCity());
    $this->assertEquals($userData->getZipCode(), $address->getPostcode());
    $this->assertEquals($userData->getCountryCode(), $address->getCountryId());
    $this->assertEquals($userData->getPhone(), $address->getTelephone());
  }

  private function assertEqualUserDataFromRegion($regionCode, $userData){
    $this->assertEquals($regionCode, $userData->getState());
  }

  private function assertEqualUserDataFromGender($gender, $userData){
    $this->assertEquals($gender, $userData->getGender());
  }

  public function testEventWithoutUserDataWhenAamSettingsNotFound(){
    $this->fbeHelper->method('getAAMSettings')->willReturn(null);

    $customer = $this->createDummyCustomer();
    $address = $this->createDummyAddress();
    $regionCode = $this->createDummyRegionCode();
    $gender = $this->createDummyGender();

    $event = ServerEventFactory::createEvent('ViewContent', array());
    $this->serverSideHelper->sendEvent($event);
    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));
    $event = $this->serverSideHelper->getTrackedEvents()[0];

    $this->assertUserDataNull($event->getUserData());
  }

  public function testEventWithoutUserDataWhenAamDisabled(){
    $settings = new AdsPixelSettings();
    $settings->setEnableAutomaticMatching(false);
    $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

    $customer = $this->createDummyCustomer();
    $address = $this->createDummyAddress();
    $regionCode = $this->createDummyRegionCode();
    $gender = $this->createDummyGender();

    $event = ServerEventFactory::createEvent('ViewContent', array());
    $this->serverSideHelper->sendEvent($event);
    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));
    $event = $this->serverSideHelper->getTrackedEvents()[0];

    $this->assertUserDataNull($event->getUserData());
  }

  public function testEventWithUserDataWhenAamEnabled(){
    $settings = new AdsPixelSettings();
    $settings->setEnableAutomaticMatching(true);
    $settings->setEnabledAutomaticMatchingFields(
      AAMSettingsFields::getAllFields()
    );

    $this->fbeHelper->method('getAAMSettings')->willReturn($settings);

    $customer = $this->createDummyCustomer();
    $address = $this->createDummyAddress();
    $regionCode = $this->createDummyRegionCode();
    $gender = $this->createDummyGender();

    $event = ServerEventFactory::createEvent('ViewContent', array());
    $this->serverSideHelper->sendEvent($event);
    $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));

    $event = $this->serverSideHelper->getTrackedEvents()[0];
    $userData = $event->getUserData();
    $this->assertEqualUserDataFromCustomer($customer, $userData);
    $this->assertEqualUserDataFromAddress($address, $userData);
    $this->assertEqualUserDataFromGender($gender, $userData);
    $this->assertEqualUserDataFromRegion($regionCode, $userData);
  }

  private function createSubset($fields){
    shuffle($fields);
    $randNum = rand()%count($fields);
    $subset = array();
    for( $i = 0; $i < $randNum; $i+=1 ){
      $subset[] = $fields[$i];
    }
    return $subset;
  }

  private function assertOnlyRequestedFieldsPresent($fieldsSubset, $userData){
    $fieldsPresent = array();
    if($userData->getLastName()){
      $fieldsPresent[] = AAMSettingsFields::LAST_NAME;
    }
    if($userData->getFirstName()){
      $fieldsPresent[] = AAMSettingsFields::FIRST_NAME;
    }
    if($userData->getEmail()){
      $fieldsPresent[] = AAMSettingsFields::EMAIL;
    }
    if($userData->getPhone()){
      $fieldsPresent[] = AAMSettingsFields::PHONE;
    }
    if($userData->getGender()){
      $fieldsPresent[] = AAMSettingsFields::GENDER;
    }
    if($userData->getCountryCode()){
      $fieldsPresent[] = AAMSettingsFields::COUNTRY;
    }
    if($userData->getZipCode()){
      $fieldsPresent[] = AAMSettingsFields::ZIP_CODE;
    }
    if($userData->getCity()){
      $fieldsPresent[] = AAMSettingsFields::CITY;
    }
    if($userData->getDateOfBirth()){
      $fieldsPresent[] = AAMSettingsFields::DATE_OF_BIRTH;
    }
    if($userData->getState()){
      $fieldsPresent[] = AAMSettingsFields::STATE;
    }
    sort($fieldsPresent);
    sort($fieldsSubset);
    $this->assertEquals($fieldsSubset, $fieldsPresent);
  }

  public function testEventWithRequestedUserDataWhenAamEnabled(){
    $possibleFields = AAMSettingsFields::getAllFields();
    $customer = $this->createDummyCustomer();
    $address = $this->createDummyAddress();
    $regionCode = $this->createDummyRegionCode();
    $gender = $this->createDummyGender();
    $settings = new AdsPixelSettings();
    $settings->setEnableAutomaticMatching(true);
    $this->fbeHelper->method('getAAMSettings')->willReturn($settings);
    for( $i = 0; $i<50; $i += 1 ){
      $fieldsSubset = $this->createSubset($possibleFields);
      $settings->setEnabledAutomaticMatchingFields($fieldsSubset);
      $event = ServerEventFactory::createEvent('ViewContent', array());
      $this->serverSideHelper->sendEvent($event);
      $this->assertEquals($i + 1, count($this->serverSideHelper->getTrackedEvents()));
      $event = $this->serverSideHelper->getTrackedEvents()[$i];
      $userData = $event->getUserData();
      $this->assertOnlyRequestedFieldsPresent($fieldsSubset, $userData);
    }
  }
}
