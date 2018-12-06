<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (file_exists(__DIR__.'/../lib/fb.php')) {
  include_once __DIR__.'/../lib/fb.php';
} else {
  include_once 'Facebook_AdsExtension_lib_fb.php';
}
class Facebook_AdsExtension_Block_Common extends Mage_Core_Block_Template {

  public function getContentType() {
    return 'product';
  }

  public function arryToContentIdString($a) {
    return implode(',', array_map(function ($i) { return '"'.$i.'"'; }, $a));
  }

  public function getCurrency() {
    return Mage::app()->getStore()->getCurrentCurrencyCode();
  }

  public function escapeQuotes($string) {
    return addslashes($string);
  }

  public function getMagentoVersion() {
    return FacebookAdsExtension::getMagentoVersion();
  }

  public function getPluginVersion() {
    return FacebookAdsExtension::version();
  }

  public function getFacebookAgentVersion() {
    return 'exmagento-'
      . $this->getMagentoVersion() . '-' . $this->getPluginVersion();
  }

  public function getFacebookPixelID() {
    return Mage::getStoreConfig('facebook_ads_toolbox/fbpixel/id');
  }

  public function pixelInitCode() {
    if (!Mage::getSingleton('customer/session')->isLoggedIn() ||
      !Mage::getStoreConfig('facebook_ads_toolbox/fbpixel/pixel_use_pii') ||
      Mage::getStoreConfig('facebook_ads_toolbox/fbpixel/pixel_use_pii') === '0') {
      return "{}";
    } else {
      try {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customerAddressId = $customer->getDefaultBilling();
        $address = Mage::getModel('customer/address')->load($customerAddressId);
        $user_info = array_filter(
                      array(
                        'em' => $customer->getEmail(),
                        'fn' => $customer->getFirstname(),
                        'ln' => $customer->getLastname(),
                        'pn' => $address->getTelephone(),
                        'gender' => $customer->getGender(),
                        'dob' => $customer->getDob(),
                        'region' => $address? $address->getRegion(): null,
                        'city' => $address? $address->getCity():null,
                        'zip' => $address? $address->getPostcode(): null
                      ),
                      function ($value) {
                        return $value !== null && $value !== '';
                      });
        return json_encode($user_info, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
      } catch (Exception $e) {
        FacebookAdsExtension::logException($e);
        return "{}";
      }
    }
  }
}
