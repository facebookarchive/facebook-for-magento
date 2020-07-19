<?php

namespace Facebook\BusinessExtension\Block\Pixel;

class Purchase extends Common {

  public function getContentIDs() {
    $product_ids = array();
    $order = $this->_fbeHelper->getObject('\Magento\Checkout\Model\Session')->getLastRealOrder();
    if ($order) {
      $items = $order->getItemsCollection();
      $product_model = $this->_fbeHelper->getObject('\Magento\Catalog\Model\Product');
      foreach ($items as $item) {
        $product = $product_model->load($item->getProductId());
        $product_ids[] = $product->getId();
      }
    }
    return $this->arrayToCommaSeperatedStringValues($product_ids);
  }

  public function getValue() {
    $order = $this->_fbeHelper->getObject('\Magento\Checkout\Model\Session')->getLastRealOrder();
    if ($order) {
      $subtotal = $order->getSubTotal();
      if($subtotal) {
        $price_helper = $this->_fbeHelper->getObject('Magento\Framework\Pricing\Helper\Data');
        return $price_helper->currency($subtotal, false, false);
      }
    } else {
      return null;
    }
  }

  public function getEventToObserveName(){
    return 'facebook_businessextension_ssapi_purchase';
  }
}
