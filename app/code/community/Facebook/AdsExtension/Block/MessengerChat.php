<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (file_exists(__DIR__.'/common.php')) {
  include_once 'common.php';
} else {
  include_once 'Facebook_AdsExtension_Block_common.php';
}

class Facebook_AdsExtension_Block_MessengerChat
  extends Facebook_AdsExtension_Block_Common {

  const PREFIX = 'facebook_ads_toolbox/messengerchat/';

  public function isMessengerChatPluginEnabled() {
    return Mage::getStoreConfig(self::PREFIX.'enabled') === '1';
  }

  public function getFacebookJSSDKVersion() {
    return Mage::getStoreConfig('facebook_ads_toolbox/fbjssdk/version');
  }

  public function getFacebookPageID() {
    return Mage::getStoreConfig(self::PREFIX.'pageid');
  }

  public function getLoggedInOutGreetingAttr() {
    $greeting_text_code = Mage::getStoreConfig(self::PREFIX.'greeting_text_code');
    if ($greeting_text_code) {
      return sprintf(
        'logged_in_greeting="%s" logged_out_greeting="%s"',
        $greeting_text_code,
        $greeting_text_code);
    } else {
      return '';
    }
  }

  public function getLocale() {
    $locale = Mage::getStoreConfig(self::PREFIX.'locale');
    return $locale ? $locale : 'en_US';
  }

  public function getThemeColorAttr() {
    $theme_color_code = Mage::getStoreConfig(self::PREFIX.'theme_color_code');
    if ($theme_color_code) {
      return sprintf('theme_color="%s"', $theme_color_code);
    } else {
      return '';
    }
  }

}
