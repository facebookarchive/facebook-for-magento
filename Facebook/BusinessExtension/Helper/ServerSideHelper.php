<?php

namespace Facebook\BusinessExtension\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\Util;
use FacebookAds\Exception\Exception;
use FacebookAds\Object\ServerSide\AdsPixelSettings;

/**
 * Helper to fire ServerSide Event.
 */
class ServerSideHelper {

  /**
   * @var \Facebook\BusinessExtension\Helper\FBEHelper
   */
  protected $_fbeHelper;

  /**
   * @var \Facebook\BusinessExtension\Helper\MagentoDataHelper
   */
  protected $_magentoDataHelper;

  /**
   * @var array FacebookAds\Object\ServerSide\Event
  */
  protected $trackedEvents;

   /**
   * Constructor
   * @param \Facebook\BusinessExtension\Helper\FBEHelper $helper
   * @param \Facebook\BusinessExtension\Helper\MagentoDataHelper $helper
   */
  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper
    ) {
    $this->_fbeHelper = $fbeHelper;
    $this->_magentoDataHelper = $magentoDataHelper;
    $this->trackedEvents = array();
  }

  // Captures user data from current session if the usePII flag is enabled
  private function addUserDataFromSession( $event ){
    $aamSettings = $this->_fbeHelper->getAAMSettings();
    $customer = $this->_magentoDataHelper->getCurrentCustomer();
    if( $aamSettings && $customer && $aamSettings->getEnableAutomaticMatching() ){
      $userData = $event->getUserData();
      $address = $this->_magentoDataHelper->getCustomerAddress($customer);
      if(in_array(AAMSettingsFields::EMAIL, $aamSettings->getEnabledAutomaticMatchingFields())){
        $userData ->setEmail(
          $customer->getEmail()
        );
      }
      if(in_array(AAMSettingsFields::FIRST_NAME, $aamSettings->getEnabledAutomaticMatchingFields())){
        $userData->setFirstName(
          $customer->getFirstname()
        );
      }
      if(in_array(AAMSettingsFields::LAST_NAME, $aamSettings->getEnabledAutomaticMatchingFields())){
        $userData->setLastName(
          $customer->getLastname()
        );
      }
      if(in_array(AAMSettingsFields::GENDER, $aamSettings->getEnabledAutomaticMatchingFields())){
        $userData->setGender(
          $this->_magentoDataHelper->getGenderAsString($customer)
        );
      }
      if(in_array(AAMSettingsFields::DATE_OF_BIRTH, $aamSettings->getEnabledAutomaticMatchingFields())){
        $userData->setDateOfBirth(
          $customer->getDob() ? date("Ymd", strtotime($customer->getDob())) : null
        );
      }
      if($address){
        if(in_array(AAMSettingsFields::PHONE, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userData->setPhone(
            $address->getTelephone()
          );
        }
        if(in_array(AAMSettingsFields::CITY, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userData ->setCity(
            $address->getCity()
          );
        }
        if(in_array(AAMSettingsFields::STATE, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userData->setState(
            $this->_magentoDataHelper->getRegionCodeForAddress($address)
          );
        }
        if(in_array(AAMSettingsFields::ZIP_CODE, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userData->setZipCode(
            $address->getPostcode()
          );
        }
        if(in_array(AAMSettingsFields::COUNTRY, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userData->setCountryCode(
            $address->getCountryId()
          );
        }
      }
    }
    return $event;
  }

  public function sendEvent($event) {
    try
    {
      $api = Api::init(null, null, $this->_fbeHelper->getAccessToken());

      $event = $this->addUserDataFromSession($event);

      $this->trackedEvents[] = $event;

      $events = array();
      array_push($events, $event);

      $request = (new EventRequest($this->_fbeHelper->getPixelID()))
          ->setEvents($events);

      $this->_fbeHelper->log('Sending event '.$event->getEventId());

      $response = $request->execute();

    } catch (Exception $e) {
      $this->_fbeHelper->log(json_encode($e));
    }
  }

  public function getTrackedEvents(){
    return $this->trackedEvents;
  }
}
