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

class Facebook_AdsExtension_Block_ViewContent
  extends Facebook_AdsExtension_Block_Common {

  public function getContentIDs() {
    $products = array();
    $products[] = Mage::registry('current_product')->getId();
    return $this->arryToContentIdString($products);
  }

  public function getContentName() {
    return $this->escapeQuotes(Mage::registry('current_product')->getName());
  }

  public function getContentCategory() {
    return Mage::registry('current_product')->getCategory() ?
      $this->escapeQuotes(
        Mage::registry('current_product')->getCategory()->getName()) : '';
  }

  public function getValue() {
    $price = Mage::registry('current_product')->getPrice(); // Get price of the current product
    $price = Mage::helper('core')->currency($price, false, false); // Get price in correct currency 
 
    return $price; 
  }
}
