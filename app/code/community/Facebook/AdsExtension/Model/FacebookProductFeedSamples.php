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

if (file_exists(__DIR__.'/FacebookProductFeed.php')) {
  include_once 'FacebookProductFeed.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FacebookProductFeed.php';
}

class FacebookProductFeedSamples extends FacebookProductFeed {

  protected function tsvescape($t) {
    // replace newlines as TSV does not allow multi-line value
    return str_replace(array("\r", "\n", "&nbsp;", "\t"), ' ', $t);
  }

  protected function buildProductAttr($attr_name, $attr_value) {
    return $this->buildProductAttrText($attr_name, $attr_value, 'tsvescape');
  }

  public function generate() {
    $MAX = 12;
    $this->conversion_needed = false;
    $this->categoryNameMap = array();
    $locale_code = Mage::app()->getLocale()->getLocaleCode();
    $symbols = Zend_Locale_Data::getList($locale_code, 'symbols');
    $this->group_separator = $symbols['group'];
    $this->decimal_separator = $symbols['decimal'];
    $this->conversion_needed = $this->isCurrencyConversionNeeded();
    $this->store_url = Mage::app()
      ->getStore()
      ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    $this->store_id = FacebookAdsExtension::getDefaultStoreId();

    $results = array();

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

    foreach ($products as $product) {
      $results[] = $this->buildProductEntry($product, $product->getName());
    }

    return $results;
  }
}
