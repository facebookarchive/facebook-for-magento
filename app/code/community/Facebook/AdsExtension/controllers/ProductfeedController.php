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

class Facebook_AdsExtension_ProductfeedController
  extends Mage_Core_Controller_Front_Action {

  public function genAction() {
    try {
      $ob = Mage::getModel('Facebook_AdsExtension/observer');
      $obins = new $ob;
      $settings_id = Mage::getStoreConfig('facebook_ads_toolbox/dia/setting/id');

      list($format, $feed, $supportzip) =
        $obins->internalGenerateFBProductFeed();
      if ($supportzip) {
        $this->getResponse()->setHeader('Content-type', 'application/x-gzip');
        list($filename, $filesize, $filecontent) = $feed->readGZip();
        $this->getResponse()->setHeader('Content-Disposition',
          'attachment; filename='.$filename);
        $this->getResponse()->setHeader('Content-Length', $filesize);
        $this->getResponse()->setBody($filecontent);
      } else {
        if ($format == 'TSV') {
          $this->getResponse()->setHeader('Content-type',
            'text/tab-separated-values');
        } else if ($format == 'XML') {
          $this->getResponse()->setHeader('Content-type', 'text/xml');
        }
        list($filename, $filesize, $filecontent) = $feed->read();
        $this->getResponse()->setHeader('Content-Disposition',
          'attachment; filename='.$filename);
        $this->getResponse()->setHeader('Content-Length', $filesize);
        $this->getResponse()->setBody($filecontent);
      }
    } catch (Exception $e) {
      $this->getResponse()->setHttpResponseCode(500);
      $this->getResponse()->setHeader('Content-type', 'text');
      if ($settings_id && $this->getRequest()->getParam('debug') === $settings_id) {
        $this->getResponse()->setBody(
          sprintf("Caught exception: %s.\n%s", $e->getMessage(), $e->getTraceAsString())
        );
      } else {
        $this->getResponse()->setBody(
          sprintf("There was a problem generating your feed: %s.", $e->getMessage())
        );
      }
    }
  }

  // Prepare the feed but don't return anything.
  public function genPingAction() {
      $ob = Mage::getModel('Facebook_AdsExtension/observer');
      $obins = new $ob;
      $time = $obins->estimateFeedGenerationTime();
      $this->getResponse()->setHeader('Content-type','text');
      $this->getResponse()->setBody(round($time));

      // This will call the genAction method above in an async request
      // so that we can still return a response from the ping action.
      try {
        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array('timeout'   => 1));
        $feed_url = FacebookAdsExtension::getFeedGenUrl();
        $curl->write(Zend_Http_Client::GET, $feed_url, '1.0');
        $curl->read();
        $curl->close();
      } catch (Exception $e) {
        // We expect the result to time out.
        FacebookAdsExtension::logException($e);
      }
  }
}
