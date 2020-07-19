<?php

namespace Facebook\BusinessExtension\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class Search implements ObserverInterface {
  /**
   * @var \Facebook\BusinessExtension\Helper\FBEHelper
   */
  protected $_fbeHelper;

  /**
   * @var \Facebook\BusinessExtension\Helper\ServerSideHelper
   */
  protected $_serverSideHelper;

  /**
   * @var \Magento\Framework\App\RequestInterface
   */
  protected $_request;

  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\ServerSideHelper $serverSideHelper,
    \Magento\Framework\App\RequestInterface $request
  ){
    $this->_fbeHelper = $fbeHelper;
    $this->_request = $request;
    $this->_serverSideHelper = $serverSideHelper;
  }

  public function getSearchQuery() {
    return htmlspecialchars(
      $this->_request->getParam('q'),
      ENT_QUOTES,
      'UTF-8');
  }

  public function execute(Observer $observer) {
    try{
      if($this->_fbeHelper->isS2SEnabled()){
        $eventId = $observer->getData('eventId');
        $customData = [
          'search_string' => $this->getSearchQuery()
        ];
        $event = ServerEventFactory::createEvent('Search', $customData, $eventId );
        $this->_serverSideHelper->sendEvent($event);
      }
    }
    catch( Exception $e ){
      $this->_fbeHelper->log(json_encode($e));
    }
    return $this;
  }
}
