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
  include_once __DIR__.'/../../../../Facebook_AdsExtension_lib_fb.php';
}

class Facebook_AdsExtension_Adminhtml_FbdebugController
  extends Mage_Adminhtml_Controller_Action {

  public function indexAction() {
    $this->ajaxAction();
  }

  public function ajaxAction() {
    $this->doQuerylogs($this->getRequest());
  }

  private function doQuerylogs($request) {
    $this->getResponse()->setHeader('Content-type', 'text');
    if ($this->getRequest()->getParam('exception')) {
      $this->getResponse()->setBody(FacebookAdsExtension::getFeedException());
    } else if ($this->getRequest()->getParam('feed')) {
      $this->getResponse()->setBody(FacebookAdsExtension::getFeedLogs());
    } else if ($this->getRequest()->getParam('store')) {
      $this->getResponse()->setBody(FacebookAdsExtension::getDefaultStoreID());
    } else if ($this->getRequest()->getParam('store_verify')) {
      $this->getResponse()->setBody(FacebookAdsExtension::getDefaultStoreID(true));
    } else {
      $this->getResponse()->setBody(FacebookAdsExtension::getLogs());
    }
  }
}
