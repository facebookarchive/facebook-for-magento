<?php
namespace Facebook\BusinessExtension\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessProductAfterSaveEventObserver implements ObserverInterface {

  const ATTR_CREATE = 'CREATE';

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
   * Call an API to product save from facebook catalog
   * after save product from Magento
   *
   * @param Observer $observer
   * @return  $this
   */
  public function execute(Observer $observer) {
    $eventProduct = $observer->getEvent()->getProduct();
    $productId = $eventProduct->getId();
    $product_name = $eventProduct->getName();
    if ($productId) {
      $feed_obj = $this->_fbeHelper->getObject('Facebook\BusinessExtension\Model\Feed\ProductFeed');
      $request_data = $feed_obj->buildProductRequest($eventProduct, $product_name, self::ATTR_CREATE);
      $request_params = array();
      $request_params[0] = $request_data;
      $response = $this->_fbeHelper->makeHttpRequest($request_params, null);
    }
    return $this;
  }
}
