<?php

namespace Facebook\BusinessExtension\Block\Pixel;

class AddToCart extends Common {

  public function getFormKey() {
    return $this->_fbeHelper->getObject('Magento\Framework\Data\Form\FormKey')->getFormKey();
  }

  public function getProductInfoUrl() {
    return sprintf('%sfbe/Pixel/ProductInfoForAddToCart', $this->_fbeHelper->getBaseUrl());
  }

}
