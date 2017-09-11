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

class Facebook_AdsExtension_Block_AddToCart
  extends Facebook_AdsExtension_Block_Common {

  private $addToCartArray;

  public function shouldFireAddToCart() {
    $a = $this->getAddToCartArray();
    return is_array($a) && count($a) > 0;
  }

  public function getContentIDs() {
    $products = $this->getAddToCartArray();
    $this->clearAddToCartArray();
    return $this->arryToContentIdString($products);
  }

  private function getAddToCartArray() {
    if ($this->addToCartArray) {
      return $this->addToCartArray;
    } else {
      $session = Mage::getSingleton("core/session", array("name"=>"frontend"));
      $this->addToCartArray = $session->getData("fbms_add_to_cart") ?: array();
      return $this->addToCartArray;
    }
  }

  private function clearAddToCartArray() {
    $session = Mage::getSingleton("core/session",  array("name"=>"frontend"));
    $session->setData("fbms_add_to_cart", array());
  }
}
