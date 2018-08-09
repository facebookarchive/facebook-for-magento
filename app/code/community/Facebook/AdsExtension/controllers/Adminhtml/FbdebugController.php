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

if (file_exists(__DIR__.'/FBProductFeedSamples.php')) {
  include_once 'FBProductFeedSamples.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FBProductFeedSamples.php';
}

class Facebook_AdsExtension_Adminhtml_FbdebugController
  extends Mage_Adminhtml_Controller_Action {

  public function indexAction() {
    if ($this->getRequest()->getParam('logs')) {
      $this->ajaxAction();
    } else {
      $this->loadLayout();
      $this->_setActiveMenu('facebook_ads_extension');
      $this->renderLayout();
    }
  }

  private function ajaxSend($response) {
    $this->getResponse()->setHeader('Content-type', 'application/json');
    $this->getResponse()->setBody(
      Mage::helper('core')->jsonEncode($response));
  }

  private function send500($response) {
    $this->getResponse()->setHttpResponseCode(500);
    $this->getResponse()->setHeader('Content-type', 'html');
    $this->getResponse()->setBody(
      Mage::helper('core')->jsonEncode($response));
  }

  public function ajaxAction() {
    if ($this->getRequest()->getParam('debugfeedsamples')) {
      FacebookAdsExtension::setDebugMode(true);
      FacebookAdsExtension::setErrorLogging();
      $samples = new FBProductFeedSamples();
      try {
        $samples = $samples->generate();
        $this->getResponse()->setBody($samples);
        FacebookAdsExtension::setDebugMode(false);
        $this->ajaxSend(array(
          'success' => true,
          'samples' => $samples,
        ));
      } catch (Throwable $e) {
        $message = $e->getMessage()." : ".$e->getTraceAsString();
        Mage::log($message, Zend_Log::EMERG, FacebookAdsExtension::DEBUGMODE_LOGFILE);
        $this->send500($message);
      }
    } else {
      $this->doQuerylogs($this->getRequest());
    }
  }

  private function doQuerylogs($request) {
    $this->getResponse()->setHeader('Content-type', 'text');
    if ($this->getRequest()->getParam('debugmode')) {
      $this->getResponse()->setBody(FacebookAdsExtension::getDebugModeLogs());
    } else if ($this->getRequest()->getParam('exception')) {
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
