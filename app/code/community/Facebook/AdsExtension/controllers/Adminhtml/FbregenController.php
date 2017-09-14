<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

class Facebook_AdsExtension_Adminhtml_FbregenController
  extends Mage_Adminhtml_Controller_Action {

  private function ajaxSend($response) {
    $this->getResponse()->setHeader('Content-type', 'application/json');
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

    // prevent HTTP GET and unlogged in request
    if ($this->getRequest()->isPost() &&
        Mage::getSingleton('admin/session')->isLoggedIn()) {
      $this->doRegenerateitnow($this->getRequest(), $this->getResponse());
      return;
    }
  }

  private function doRegenerateitnow($request, $response) {
    $ob = Mage::getModel('Facebook_AdsExtension/observer');
    $use_cache = $request->getPost('useCache', false);
    $obins = new $ob;
    $obins->internalGenerateFBProductFeed(false, $use_cache);

    $this->ajaxSend(array(
      'success' => true,
    ));
  }
}
