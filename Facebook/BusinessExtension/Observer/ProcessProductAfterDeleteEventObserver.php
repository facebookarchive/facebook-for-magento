<?php

namespace Facebook\BusinessExtension\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Helper\Context;

class ProcessProductAfterDeleteEventObserver implements ObserverInterface {

  /**
   * @var \Facebook\BusinessExtension\Helper\FBEHelper
   */
  protected $_fbeHelper;

  /**
   * Constructor
   * @param \Facebook\BusinessExtension\Helper\FBEHelper $helper
   */
  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $helper) {
    $this->_fbeHelper = $helper;
  }

  /**
   * Call an API to product delete from facebook catalog
   * after delete product from Magento
   *
   * @param Observer $observer
   * @return  $this
   */
  public function execute(Observer $observer) {
    $eventProduct = $observer->getEvent()->getProduct();
    $productId = $eventProduct->getId();
    if ($productId) {
      $request_data = array();
      $request_data['method'] = 'DELETE';
      $request_data['retailer_id'] = $productId;
      $request_params = array();
      $request_params[0] = $request_data;
      $response = $this->_fbeHelper->makeHttpRequest($request_params, null);
    }
    return $this;
  }
}
