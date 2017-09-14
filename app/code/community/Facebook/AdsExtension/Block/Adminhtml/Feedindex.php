<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (file_exists(__DIR__.'/../../lib/fb.php')) {
  include_once __DIR__.'/../../lib/fb.php';
} else {
  include_once 'Facebook_AdsExtension_lib_fb.php';
}

if (file_exists(__DIR__.'/../../Model/FBProductFeed.php')) {
  include_once __DIR__.'/../../Model/FBProductFeed.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FBProductFeed.php';
}

class Facebook_AdsExtension_Block_Adminhtml_Feedindex
  extends Mage_Adminhtml_Block_Template {

  public function getBaseUrl() {
    return FacebookAdsExtension::getBaseUrlMedia();
  }

  public function getAjaxRoute() {
    return Mage::helper('adminhtml')->getUrl(
      'adminhtml/fbfeed/ajax');
  }

  public function getAjaxGenerateNowRoute() {
    return Mage::helper('adminhtml')->getUrl(
      'adminhtml/fbregen/ajax');
  }

  public function getAjaxLastRunLogsRoute() {
    return Mage::helper('adminhtml')->getUrl(
      'adminhtml/fbfeedlog/ajax');
  }

  public function fetchFeedSetupEnabled() {
    $setup = FBProductFeed::getCurrentSetup();
    return $setup['enabled'];
  }

  public function fetchFeedSetupFormat() {
    $setup = FBProductFeed::getCurrentSetup();
    return $setup['format'];
  }
}
