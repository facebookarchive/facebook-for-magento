<?php

namespace Facebook\BusinessExtension\Block\Pixel;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\ObjectManagerInterface;
use \Facebook\BusinessExtension\Helper\AAMSettingsFields;

class Common extends \Magento\Framework\View\Element\Template {
  /**
   * @var \Magento\Framework\ObjectManagerInterface
   */
  protected $_objectManager;
  protected $_registry;
  protected $_fbeHelper;
  protected $_magentoDataHelper;

  public function __construct(
    Context $context,
    ObjectManagerInterface $objectManager,
    \Magento\Framework\Registry $registry,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper,
    array $data = []) {
    parent::__construct($context, $data);
    $this->_objectManager = $objectManager;
    $this->_registry = $registry;
    $this->_fbeHelper = $fbeHelper;
    $this->_magentoDataHelper = $magentoDataHelper;
  }

  public function arrayToCommaSeperatedStringValues($a) {
    return implode(',', array_map(function ($i) { return '"'.$i.'"'; }, $a));
  }

  public function escapeQuotes($string) {
    return addslashes($string);
  }

  public function getFacebookPixelID() {
    return $this->_fbeHelper->getPixelID();
  }

  public function getPixelInitCode(){
    $aamSettings = $this->_fbeHelper->getAAMSettings();
    $customer = $this->_magentoDataHelper->getCurrentCustomer();
    if($customer && $aamSettings && $aamSettings->getEnableAutomaticMatching()){
      try{
        $address = $this->_magentoDataHelper->getCustomerAddress($customer);
        $userInfo = array();
        if(in_array(AAMSettingsFields::EMAIL, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userInfo['em'] = $customer->getEmail();
        }
        if(in_array(AAMSettingsFields::FIRST_NAME, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userInfo['fn'] = $customer->getFirstname();
        }
        if(in_array(AAMSettingsFields::LAST_NAME, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userInfo['ln'] = $customer->getLastname();
        }
        if(in_array(AAMSettingsFields::GENDER, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userInfo['ge'] = $this->_magentoDataHelper->getGenderAsString($customer);
        }
        if(in_array(AAMSettingsFields::DATE_OF_BIRTH, $aamSettings->getEnabledAutomaticMatchingFields())){
          $userInfo['db'] = $customer->getDob() ? date("Ymd", strtotime($customer->getDob())) : null;
        }
        if($address){
          if(in_array(AAMSettingsFields::PHONE, $aamSettings->getEnabledAutomaticMatchingFields())){
            $userInfo['ph'] = $address->getTelephone();
          }
          if(in_array(AAMSettingsFields::CITY, $aamSettings->getEnabledAutomaticMatchingFields())){
            $userInfo['ct'] = $address->getCity();
          }
          if(in_array(AAMSettingsFields::STATE, $aamSettings->getEnabledAutomaticMatchingFields())){
            $userInfo['st'] = $this->_magentoDataHelper->getRegionCodeForAddress($address);
          }
          if(in_array(AAMSettingsFields::ZIP_CODE, $aamSettings->getEnabledAutomaticMatchingFields())){
            $userInfo['zp'] = $address->getPostcode();
          }
          if(in_array(AAMSettingsFields::COUNTRY, $aamSettings->getEnabledAutomaticMatchingFields())){
            $userInfo['cn'] = $address->getCountryId(); //Added for upward compatibility
          }
        }
        return json_encode(array_filter($userInfo), JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
      }
      catch(Exception $e) {
        $this->fbeHelper->logException($e);
      }
    }
    return '{}';
  }

  public function getSource() {
    return 'magento2';
  }

  public function getMagentoVersion() {
    return $this->_fbeHelper->getMagentoVersion();
  }

  public function getPluginVersion() {
    return $this->_fbeHelper->getPluginVersion();
  }

  public function getFacebookAgentVersion() {
    return sprintf(
      'exmagento2-%s-%s',
      $this->getMagentoVersion(),
      $this->getPluginVersion());
  }

  public function getContentType() {
    return 'product';
  }

  public function getCurrency() {
    return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
  }

  public function logEvent($pixel_id, $pixel_event) {
    $this->_fbeHelper->logPixelEvent($pixel_id, $pixel_event);
  }

  public function getEventToObserveName(){
    return '';
  }

  public function trackServerEvent($eventId){
    $this->_eventManager->dispatch($this->getEventToObserveName(), ['eventId' => $eventId]);
  }
}
