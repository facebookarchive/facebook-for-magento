<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

class Facebook_AdsExtension_Block_AddToWishlist 
  extends Facebook_AdsExtension_Block_Common {

  private $AddToWishlistArray;

  public function shouldFireAddToWishlist() {
    $a = $this->getAddToWishlistArray();
    return is_array($a) && count($a) > 0;
  }

  public function getContentIDs() {
    $products = $this->getAddToWishlistArray();
    $this->clearAddToWishlistArray();
    return $this->arryToContentIdString($products);
  }

  private function getAddToWishlistArray() {
    if ($this->AddToWishlistArray) {
      return $this->AddToWishlistArray;
    } else {
      $session = Mage::getSingleton("core/session", array("name"=>"frontend"));
      $this->AddToWishlistArray = $session->getData("fbms_add_to_wishlist") ?: array();
      return $this->AddToWishlistArray;
    }
  }

  private function clearAddToWishlistArray() {
    $session = Mage::getSingleton("core/session",  array("name"=>"frontend"));
    $session->setData("fbms_add_to_wishlist", array());
  }
}
