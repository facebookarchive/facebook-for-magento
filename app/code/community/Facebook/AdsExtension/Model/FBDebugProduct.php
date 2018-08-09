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
  include_once 'Facebook_AdsExtension_lib_fb.php';
}

// Wrapper class for Magento's Product for Debugging
class FBDebugProduct {
  public function __construct($product, $count, $debug_origin = null) {
    $this->product = $product;
    $this->name = "Product $count";
    $this->debug_origin = $debug_origin;
  }

  private function logd($string) {
    if ($this->debug_origin) {
      $this->debug_origin->logd("get ".$this->name." ".$string);
    }
  }

  // Call method on original magento product if it isn't defined.
  public function __call($function, $args) {
      $this->logd("function ".$function."");
      return call_user_func_array(array($this->product, $function), $args);
  }
}
