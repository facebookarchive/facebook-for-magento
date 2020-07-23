<?php

namespace Facebook\BusinessExtension\Block\Pixel;

class InitiateCheckout extends Common {

  public function getContentIDs() {
    $product_ids = array();
    $cart = $this->_fbeHelper->getObject('\Magento\Checkout\Model\Cart');
    $items = $cart->getQuote()->getAllVisibleItems();
    $product_model = $this->_fbeHelper->getObject('\Magento\Catalog\Model\Product');
    foreach ($items as $item) {
      $product = $product_model->load($item->getProductId());
      $product_ids[] = $product->getId();
    }
    return $this->arrayToCommaSeperatedStringValues($product_ids);
  }

  public function getValue() {
    $cart = $this->_fbeHelper->getObject('\Magento\Checkout\Model\Cart');
    if(!$cart || !$cart->getQuote()) {
      return null;
    }
    $subtotal = $cart->getQuote()->getSubtotal();
    if ($subtotal) {
      $price_helper = $this->_fbeHelper->getObject('Magento\Framework\Pricing\Helper\Data');
      return $price_helper->currency($subtotal, false, false);
    } else {
      return null;
    }
  }

  public function getContents() {
    $cart = $this->_fbeHelper->getObject('\Magento\Checkout\Model\Cart');
    if(!$cart || !$cart->getQuote()) {
      return '';
    }
    $contents = array();
    $items = $cart->getQuote()->getAllVisibleItems();
    $product_model = $this->_fbeHelper->getObject('\Magento\Catalog\Model\Product');
    $priceHelper = $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data');
    foreach ($items as $item) {
      $product = $product_model->load($item->getProductId());
      $price = $priceHelper->currency($product->getFinalPrice(), false, false);
      $content = '{id:"'.$product->getId().'",quantity:'.intval($item->getQty()).',item_price:'.$price."}";
      $contents[] = $content;
    }
    return implode(',', $contents );
  }

  public function getNumItems() {
    $cart = $this->_fbeHelper->getObject('\Magento\Checkout\Model\Cart');
    if(!$cart || !$cart->getQuote()) {
      return null;
    }
    $numItems = 0;
    $items = $cart->getQuote()->getAllVisibleItems();
    foreach ($items as $item) {
      $numItems += $item->getQty();
    }
    return $numItems;
  }

  public function getEventToObserveName(){
    return 'facebook_businessextension_ssapi_initiate_checkout';
  }
}
