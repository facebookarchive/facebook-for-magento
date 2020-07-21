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

class AddToCart implements ObserverInterface {

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
   * @var \Magento\Framework\App\RequestInterface
   */
  protected $_request;

  /**
   * Constructor
   * @param \Facebook\BusinessExtension\Helper\FBEHelper $helper
   * @param \Facebook\BusinessExtension\Helper\MagentoDataHelper $helper
   * @param \Facebook\BusinessExtension\Helper\ServerSideHelper $serverSideHelper
   * @param \Magento\Framework\App\RequestInterface $request
   */
  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    \Facebook\BusinessExtension\Helper\MagentoDataHelper $magentoDataHelper,
    \Facebook\BusinessExtension\Helper\ServerSideHelper $serverSideHelper,
    \Magento\Framework\App\RequestInterface $request) {
    $this->_fbeHelper = $fbeHelper;
    $this->_magentoDataHelper = $magentoDataHelper;
    $this->_serverSideHelper = $serverSideHelper;
    $this->_request = $request;
  }

  /**
   * Execute action method for the Observer
   *
   * @param Observer $observer
   * @return  $this
   */
  public function execute(Observer $observer) {
    try{
      $eventId = $observer->getData('eventId');
      $productSku = $this->_request->getParam('product_sku', null);
      $product = $this->_magentoDataHelper->getProductWithSku($productSku);
      if ($product->getId()){
        $customData = [
          'currency' => $this->_magentoDataHelper->getCurrency(),
          'value' => $this->_magentoDataHelper->getValueForProduct($product),
          'content_type' => 'product',
          'content_ids' => array($product->getId()),
          'content_category' => $this->_magentoDataHelper->getCategoriesForProduct($product),
          'content_name' => $product->getName()
        ];
        $event = ServerEventFactory::createEvent('AddToCart', $customData, $eventId );
        $this->_serverSideHelper->sendEvent($event);
      }
    }
    catch( Exception $e ){
      $this->_fbeHelper->log(json_encode($e));
    }
    return $this;
  }
}
