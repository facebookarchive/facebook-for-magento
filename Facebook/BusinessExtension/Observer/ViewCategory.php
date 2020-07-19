<?php

namespace Facebook\BusinessExtension\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class ViewCategory implements ObserverInterface {
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

  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\ServerSideHelper $serverSideHelper,
    \Magento\Framework\Registry $registry
  ){
    $this->_fbeHelper = $fbeHelper;
    $this->_registry = $registry;
    $this->_serverSideHelper = $serverSideHelper;
  }

  public function execute(Observer $observer) {
    try{
      if($this->_fbeHelper->isS2SEnabled()){
        $eventId = $observer->getData('eventId');
        $customData = array();
        $category = $this->_registry->registry('current_category');
        if ($category) {
          $customData['content_category'] = addslashes($category->getName());
        }
        $event = ServerEventFactory::createEvent('ViewCategory', $customData, $eventId );
        $this->_serverSideHelper->sendEvent($event);
      }
    }
    catch( Exception $e ){
      $this->_fbeHelper->log(json_encode($e));
    }
    return $this;
  }
}
