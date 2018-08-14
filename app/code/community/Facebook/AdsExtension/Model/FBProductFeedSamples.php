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

if (file_exists(__DIR__.'/FBProductFeed.php')) {
  include_once 'FBProductFeed.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FBProductFeed.php';
}

class FBProductFeedSamples extends FBProductFeed {
  public $debug_mode = false;
   // TODO : Print stacktrace in verbose mode on every log line.
  public $verbose = false;
  public $previous_debug_step = null;

  public function __construct() {
    $this->debug_mode = FacebookAdsExtension::$debug_mode;
  }

  protected function tsvescape($t) {
    // replace newlines as TSV does not allow multi-line value
    return str_replace(array("\r", "\n", "&nbsp;", "\t"), ' ', $t);
  }

  protected function buildProductAttr($attr_name, $attr_value) {
    $this->logd("build product $attr_name");
    return $this->buildProductAttrText($attr_name, $attr_value, 'tsvescape');
  }

  public function generate() {
    $this->logd('initialize sample generation');
    $MAX = 12;
    $this->conversion_needed = false;
    $this->categoryNameMap = array();

    $this->logd('get locale code');
    $locale_code = Mage::app()->getLocale()->getLocaleCode();

    $this->logd('get zend locale data');
    $symbols = Zend_Locale_Data::getList($locale_code, 'symbols');
    $this->group_separator = $symbols['group'];
    $this->decimal_separator = $symbols['decimal'];

    $this->logd('determine whether currency conversion is needed');
    $this->conversion_needed = $this->isCurrencyConversionNeeded();

    $this->logd('get store url');
    $this->store_url = Mage::app()
      ->getStore()
      ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

    $this->logd('get default store ID');
    $this->store_id = FacebookAdsExtension::getDefaultStoreId();

    $results = array();
    $this->logd("load $MAX sample products from your store");
    $products = Mage::getModel('catalog/product')->getCollection()
      ->addStoreFilter($this->store_id)
      ->addAttributeToSelect('*')
      ->addAttributeToFilter('visibility',
          array(
            'neq' =>
              Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
          )
      )
      ->addAttributeToFilter('status',
          array(
            'neq' =>
              Mage_Catalog_Model_Product_Status::STATUS_DISABLED
          )
      )
      ->setPageSize($MAX)
      ->setCurPage(0)
      ->addUrlRewrite();

    $count = 0;
    if ($products) {
      $this->logd("Parse ".count($products)." products");
    } else {
      $this->logd("read returned products FAILED. DB Query returned nothing. You need at least 1 VISIBLE ENABLED product to use this extension.");
      return $results;
    }

    foreach ($products as $product) {
      $count++;
      $this->logd("Load Product $count");
      $results[] = ($this->debug_mode) ?
        $this->buildProductEntryDebug($product, $count) :
        $this->buildProductEntry($product, $product->getName());
    }

    $this->logd('Finish fetching product samples');
    $this->logd('');
    return $results;
  }

  public function logd($step_name = null) {
    if ($this->debug_mode) {
      if ($this->previous_debug_step) {
        Mage::log("Trying to $this->previous_debug_step ... SUCCESS", Zend_Log::INFO, FacebookAdsExtension::DEBUGMODE_LOGFILE);
      } else {
        Mage::log("INITIALIZING NEW DEBUG RUN", Zend_Log::INFO, FacebookAdsExtension::DEBUGMODE_LOGFILE);
      }
      if ($step_name) {
        Mage::log("Trying to $step_name ...", Zend_Log::INFO, FacebookAdsExtension::DEBUGMODE_LOGFILE);
        $this->previous_debug_step = $step_name;
      }
    }
  }

  private function buildProductEntryDebug($product, $count) {
    $name = $product->getName();
    $this->logd("Initialize Debug of Product $count with Name $name");
    if (file_exists(__DIR__.'/FBDebugProduct.php')) {
      include_once 'FBDebugProduct.php';
    } else {
      include_once 'Facebook_AdsExtension_Model_FBDebugProduct.php';
    }
    $product2 = new FBDebugProduct($product, $count, $this);
    $this->logd("Fetch Stock Item of Product $count");
    $this->stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    $this->logd("Build Product Entry of Product $count");
    return $this->buildProductEntry($product2, $name);
  }
}
