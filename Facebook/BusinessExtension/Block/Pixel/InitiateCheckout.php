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

  public function getEventToObserveName(){
    return 'facebook_businessextension_ssapi_initiate_checkout';
  }
}
