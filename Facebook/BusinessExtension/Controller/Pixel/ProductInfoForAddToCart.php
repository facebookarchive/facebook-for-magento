<?php

namespace Facebook\BusinessExtension\Controller\Pixel;

use \Facebook\BusinessExtension\Helper\EventIdGenerator;

class ProductInfoForAddToCart extends \Magento\Framework\App\Action\Action {

  protected $_resultJsonFactory;
  protected $_productFactory;
  protected $_fbeHelper;
  protected $_eventManager;

  public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    \Magento\Catalog\Model\ProductFactory $productFactory,
    \Facebook\BusinessExtension\Helper\FBEHelper $helper,
    \Magento\Framework\Event\ManagerInterface $eventManager
  ) {
    parent::__construct($context);
    $this->_resultJsonFactory = $resultJsonFactory;
    $this->_productFactory = $productFactory;
    $this->_fbeHelper = $helper;
    $this->_eventManager = $eventManager;
  }

  private function getCategory($product) {
    $category_ids = $product->getCategoryIds();
    if (count($category_ids) > 0) {
      $category_names = array();
      $category_model = $this->_fbeHelper->getObject('Magento\Catalog\Model\Category');
      foreach ($category_ids as $category_id) {
        $category = $category_model->load($category_id);
        $category_names[] = $category->getName();
      }
      return addslashes(implode(',', $category_names));
    } else {
      return null;
    }
  }

  private function getValue($product) {
    if ($product && $product->getId()) {
      $price = $product->getFinalPrice();
      $price_helper = $this->_fbeHelper->getObject('Magento\Framework\Pricing\Helper\Data');
      return $price_helper->currency($price, false, false);
    } else {
      return null;
    }
  }

  private function getProductInfo($product_sku, $eventId) {
    $response_data = array();
    $product = $this->_productFactory->create();
    $product->load($product->getIdBySku($product_sku));
    if ($product->getId()) {
      $response_data['event_id'] = $eventId;
      $response_data['id'] = $product->getId();
      $response_data['name'] = $product->getName();
      $response_data['category'] = $this->getCategory($product);
      $response_data['value'] = $this->getValue($product);
    }
    $result = $this->_resultJsonFactory->create();
    $result->setData(array_filter($response_data));
    return $result;
  }

  private function validateFormKey($form_key) {
    $fk = $this->_fbeHelper->getObject('Magento\Framework\Data\Form\FormKey')->getFormKey();
    return $fk === $form_key;
  }

  public function execute() {
    $form_key = $this->getRequest()->getParam('form_key', null);
    $product_sku = $this->getRequest()->getParam('product_sku', null);
    if ($form_key && $this->validateFormKey($form_key) && $product_sku) {
      $eventId = null;
      if($this->_fbeHelper->isS2SEnabled()){
        $eventId = EventIdGenerator::guidv4();
        $this->trackServerEvent($eventId);
      }
      return $this->getProductInfo($product_sku, $eventId);
    } else {
      $this->_redirect('noroute');
    }
  }

  public function trackServerEvent($eventId){
    $this->_eventManager->dispatch('facebook_businessextension_ssapi_add_to_cart', ['eventId' => $eventId]);
  }
}
