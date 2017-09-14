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

if (file_exists(__DIR__.'/../../Model/FBProductFeed.php')) {
  include_once __DIR__.'/../../Model/FBProductFeed.php';
} else if (file_exists(__DIR__.'/../../../../Facebook_AdsExtension_Model_FBProductFeed.php')) {
  include_once __DIR__.'/../../../../Facebook_AdsExtension_Model_FBProductFeed.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FBProductFeed.php';
}

class Facebook_AdsExtension_Adminhtml_FbfeedController
  extends Mage_Adminhtml_Controller_Action {

  public function indexAction() {
    $this->loadLayout();
    $this->renderLayout();
  }

  private function ajaxSend($response) {
    $this->getResponse()->setHeader('Content-type', 'application/json');
    $this->getResponse()->setBody(
      Mage::helper('core')->jsonEncode($response));
  }

  private function send500($response) {
    $this->getResponse()->setHttpResponseCode(500);
    $this->getResponse()->setBody(
      Mage::helper('core')->jsonEncode($response));
  }

  public function ajaxAction() {
    $isAjax = $this->getRequest()->isAjax();
    if (!$isAjax) {
      $this->getResponse()->setRedirect(
        Mage::helper('adminhtml')->getUrl(
          'adminhtml/fbfeed/index'));
      return;
    }

    if ($this->getRequest()->isPost()) {
      $this->doUpdateSettings($this->getRequest());
      return;
    }

    // in default. get request will return lastrunlogs
    $this->doQuerylastrunlogs($this->getRequest());
  }

  private function doUpdateSettings($request) {
    $enabled = $request->getPost('enabled', null);
    $format = $request->getPost('format', null);

    if ($enabled &&
        (!is_string($enabled) ||
         !($enabled === 'true' || $enabled === 'false'))) {
      $this->send500(array(
        'error' => 'param enabled can only be true/false.',
      ));
      return;
    }

    if ($format &&
        (!is_string($format) || !($format === 'TSV' || $format === 'XML'))) {
      $this->send500(array(
        'error' => 'param format can only be "TSV"/"XML". ',
      ));
      return;
    }

    if ($enabled) {
      Mage::getModel('core/config')->saveConfig(
        FBProductFeed::PATH_FACEBOOK_ADSEXTENSION_FEED_GENERATION_ENABLED,
        ($enabled === 'true'));
    }

    if ($format) {
      Mage::getModel('core/config')->saveConfig(
        FBProductFeed::PATH_FACEBOOK_ADSEXTENSION_FEED_GENERATION_FORMAT,
        $format);
    }

    $this->ajaxSend(array(
      'success' => true,
    ));
  }

  private function doQuerylastrunlogs($request) {
    $response = array(
      'success' => true,
    );
    $logfile = Mage::getBaseDir('log').'/'.FBProductFeed::LOGFILE;
    $fp = fopen($logfile, 'r');
    if (!$fp) {
      $response['lastrunlogs'] =
        'Read '.FBProductFeed::LOGFILE.' error!';
      $this->ajaxSend($response);
      return;
    }

    $pos = -1; // Skip final new line character (Set to -1 if not present)
    $lines = array();
    $currentLine = '';
    $found = false;
    while (-1 !== fseek($fp, $pos, SEEK_END)) {
      $char = fgetc($fp);
      if (PHP_EOL == $char) {
        $lines[] = $currentLine;
        if (FacebookAdsExtension::endsWith(
          $currentLine,
          'feed generation start...')) {
          $found = true;
          break;
        }
        $currentLine = '';
      } else {
        $currentLine = $char . $currentLine;
      }
      $pos--;
    }
    if ($found) {
      $response['lastrunlogs'] = implode("\n", array_reverse($lines));
    } else {
      $response['lastrunlogs'] = 'Can not find last run logs!';
    }

    $this->ajaxSend($response);
  }
}
