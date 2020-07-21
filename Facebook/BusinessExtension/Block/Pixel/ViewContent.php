<?php

namespace Facebook\BusinessExtension\Block\Pixel;

class ViewContent extends Common {

  public function getContentIDs() {
    $product_ids = array();
    $product = $this->_registry->registry('current_product');
    if ($product && $product->getId()) {
      $product_ids[] = $product->getId();
    }
    return $this->arrayToCommaSeperatedStringValues($product_ids);
  }

  public function getContentName() {
    $product = $this->_registry->registry('current_product');
    if ($product && $product->getId()) {
      return $this->escapeQuotes($product->getName());
    } else {
      return null;
    }
  }

  public function getContentCategory() {
    $product = $this->_registry->registry('current_product');
    $category_ids = $product->getCategoryIds();
    if (count($category_ids) > 0) {
      $category_names = array();
      $category_model = $this->_fbeHelper->getObject('Magento\Catalog\Model\Category');
      foreach ($category_ids as $category_id) {
        $category = $category_model->load($category_id);
        $category_names[] = $category->getName();
      }
      return $this->escapeQuotes(implode(',', $category_names));
    } else {
      return null;
    }
  }

  public function getValue() {
    $product = $this->_registry->registry('current_product');
    if ($product && $product->getId()) {
      $price = $product->getFinalPrice();
      $price_helper = $this->_fbeHelper->getObject('Magento\Framework\Pricing\Helper\Data');
      return $price_helper->currency($price, false, false);
    } else {
      return null;
    }
  }

  public function getEventToObserveName(){
    return 'facebook_businessextension_ssapi_view_content';
  }
}
