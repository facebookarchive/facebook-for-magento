<?php

namespace Facebook\BusinessExtension\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class ViewContent implements ObserverInterface {
  /**
   * @var \Facebook\BusinessExtension\Helper\FBEHelper
   */
  protected $_fbeHelper;

  /**
   * @var \Facebook\BusinessExtension\Helper\ServerSideHelper
   */
  protected $_serverSideHelper;

  /**
   * \Magento\Framework\Registry
   */
  protected $_registry;

  /**
   * @var \Facebook\BusinessExtension\Helper\MagentoDataHelper
   */
  protected $_magentoDataHelper;

  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\ServerSideHelper $serverSideHelper,
    \Facebook\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper,
    \Magento\Framework\Registry $registry
  ){
    $this->_fbeHelper = $fbeHelper;
    $this->_registry = $registry;
    $this->_serverSideHelper = $serverSideHelper;
    $this->_magentoDataHelper = $magentoDataHelper;
  }

  public function execute(Observer $observer) {
    try{
      if($this->_fbeHelper->isS2SEnabled()){
        $eventId = $observer->getData('eventId');
        $customData = [
          'currency' => $this->_magentoDataHelper->getCurrency(),
          'content_type' => 'product'
        ];
        $product = $this->_registry->registry('current_product');
        if ($product && $product->getId()) {
          $customData['value'] = $this->_magentoDataHelper->getValueForProduct($product);
          $customData['content_ids'] = array( $product->getId() );
          $customData['content_category'] = $this->_magentoDataHelper->getCategoriesForProduct($product);
          $customData['content_name'] = $product->getName();
          $customData['contents'] = [
            array(
              'product_id' => $product->getId(),
              'item_price' => $this->_magentoDataHelper->getValueForProduct($product)
            )
          ];
        }
        $event = ServerEventFactory::createEvent('ViewContent', array_filter($customData), $eventId );
        $this->_serverSideHelper->sendEvent($event);
      }
    }
    catch( Exception $e ){
      $this->_fbeHelper->log(json_encode($e));
    }
    return $this;
  }
}
