<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

// This controller can be reused to set various FB advanced options.

if (file_exists(__DIR__.'/../../lib/fb.php')) {
  include_once __DIR__.'/../../lib/fb.php';
} else {
  include_once __DIR__.'/../../../../Facebook_AdsExtension_lib_fb.php';
}

class Facebook_AdsExtension_Adminhtml_FbsetoptionController
  extends Mage_Adminhtml_Controller_Action {

  public function indexAction() {
    $this->loadLayout();
    $this->renderLayout();
  }

  public function ajaxAction() {
    try {
      $option = $this->getRequest()->getParam('option');
      $option_value = $this->getRequest()->getParam('option_value');
      $option = preg_replace("/[^A-Za-z0-9\/_]/", '', $option);
      if ($option !== null && strlen($option) < 100) {
        // security tofix
        Mage::getModel('core/config')->saveConfig(
          'facebook_ads_toolbox/dia/'.$option,
          $option_value);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(
          Mage::helper('core')->jsonEncode(array('success' => true)));
      } else {
        $this->reportFailure($option.':'.$option_value, null);
      }
    } catch (Exception $e) {
      $this->reportFailure($option.':'.$option_value, $e);
    }
  }

  private function reportFailure($err_string, $e) {
    if ($e) {
      FacebookAdsExtension::logException($e);
    }
    $msg = Mage::helper('core/url')->getCurrentUrl();
    FacebookAdsExtension::log("Set Option Failure : ".$msg." ".$err_string);

    Mage::throwException(
      'Set Option failed:'.($err_string ?: 'null'));
  }
}
