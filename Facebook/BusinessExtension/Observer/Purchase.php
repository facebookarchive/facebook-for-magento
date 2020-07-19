<?php
namespace Facebook\BusinessExtension\Observer;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use Facebook\BusinessExtension\Helper\ServerEventFactory;

class Purchase implements ObserverInterface {

  /**
   * @var \Facebook\BusinessExtension\Helper\FBEHelper
   */
  protected $_fbeHelper;

  /**
   * @var \Facebook\BusinessExtension\Helper\MagentoDataHelper
   */
  protected $_magentoDataHelper;

  /**
   * @var \Facebook\BusinessExtension\Helper\ServerSideHelper
   */
  protected $_serverSideHelper;

  /**
   * Constructor
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Facebook\BusinessExtension\Helper\FBEHelper $helper
   * @param \Facebook\BusinessExtension\Helper\MagentoDataHelper $helper
   */
  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper,
    \Facebook\BusinessExtension\Helper\ServerSideHelper $serverSideHelper
    ) {
    $this->_fbeHelper = $fbeHelper;
    $this->_magentoDataHelper = $magentoDataHelper;
    $this->_serverSideHelper = $serverSideHelper;
  }

  /**
   * Execute action method for the Observer
   *
   * @param Observer $observer
   * @return  $this
   */
  public function execute(Observer $observer) {
    try{
      if($this->_fbeHelper->isS2SEnabled()){
        $eventId = $observer->getData('eventId');
        $customData = [
          'currency' => $this->_magentoDataHelper->getCurrency(),
          'value' => $this->_magentoDataHelper->getOrderTotal(),
          'content_type' => 'product',
          'content_ids' => $this->_magentoDataHelper->getOrderContentIds(),
          'contents' => $this->_magentoDataHelper->getOrderContents(),
          'order_id' => strval($this->_magentoDataHelper->getOrderId())
        ];
        $event = ServerEventFactory::createEvent('Purchase', array_filter($customData), $eventId );
        $this->_serverSideHelper->sendEvent($event);
      }
    }
    catch( Exception $e ){
      $this->_fbeHelper->log(json_encode($e));
    }
    return $this;
  }
}
