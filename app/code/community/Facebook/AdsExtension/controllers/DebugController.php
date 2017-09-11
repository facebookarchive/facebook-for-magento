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
  include_once __DIR__.'/../../../Facebook_AdsExtension_lib_fb.php';
}

class Facebook_AdsExtension_DebugController
  extends Mage_Core_Controller_Front_Action {

  // The debug key is stored in core_config_data and accessible only to
  // the store owner and to facebook.
  // The logs displayed are only facebook specific logs from the ads extension.
  public function genAction() {
    $debug_key = $this->getRequest()->getParam('debug');
    if ($debug_key && $debug_key === FacebookAdsExtension::getDebugKey()) {
      $this->getResponse()->setHeader('Content-type', 'text');
      $feed = $this->getRequest()->getParam('feed');
      if ($feed && $feed == 'exception') {
        $this->getResponse()->setBody(FacebookAdsExtension::getFeedException());
      } else if ($feed) {
        $this->getResponse()->setBody(FacebookAdsExtension::getFeedLogs());
      } else {
        $this->getResponse()->setBody(FacebookAdsExtension::getLogs());
      }
    } else {
      $this->getResponse()->setHttpResponseCode(404);
    }
  }
}
