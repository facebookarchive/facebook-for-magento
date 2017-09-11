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

class Facebook_AdsExtension_Adminhtml_FbstoreController
  extends Mage_Adminhtml_Controller_Action {

  public function indexAction() {
    $this->loadLayout();
    $this->renderLayout();
  }

  public function ajaxAction() {
    try {
      $store_id = $this->getRequest()->getParam('storeId');
      if (is_numeric($store_id)) {
        Mage::getModel('core/config')->saveConfig(
          'facebook_ads_toolbox/fbstore/id',
          $store_id);
      }

      $product_count = FacebookAdsExtension::getTotalVisibleProducts($store_id);
      $this->getResponse()->setHeader('Content-type', 'application/json');
      $this->getResponse()->setBody(
        Mage::helper('core')->jsonEncode(array(
            'success' => true,
            'product_count' => $product_count
          )));
    } catch (Exception $e) {
      $this->reportFailure($store_id, $e);
    }
  }

  private function reportFailure($store_id, $e) {
    if ($e) {
      FacebookAdsExtension::logException($e);
    }
    $msg = Mage::helper('core/url')->getCurrentUrl();
    FacebookAdsExtension::log("Set Store ID Failure : ".$msg." ".$store_id);

    $this->getResponse()->setHeader('Content-type', 'application/json');
    $this->getResponse()->setHttpResponseCode(500);
    $this->getResponse()->setBody(
        Mage::helper('core')->jsonEncode(array(
            'exception' => $e->getMessage()
          )));
  }
}
