<?php

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

class Fbfeedpush extends AbstractAjax {

  public function __construct (
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    \Facebook\BusinessExtension\Helper\FBEHelper $helper,
    \Magento\Customer\Model\Session $customerSession
  ) {
    parent::__construct($context, $resultJsonFactory, $helper);
    $this->_customerSession = $customerSession;
  }

  public function executeForJson() {
    $response = array();
    $external_business_id = $this->_fbeHelper->getConfigValue('fbe/external/id');
    $this->_fbeHelper->log("Existing external business id --- ". $external_business_id);
    if($external_business_id) {
      $response['success'] = false;
      $response['message'] = 'One time feed push is completed at the time of setup';
      return $response;
    }
    try {
      $access_token = $this->getRequest()->getParam('accessToken');
      $external_business_id = $this->getRequest()->getParam('externalBusinessId');
      $catalog_id = $this->getRequest()->getParam('catalogId');
      $this->saveCatalogId($catalog_id);
      if($access_token) {
        $feed_obj = $this->_fbeHelper->getObject('Facebook\BusinessExtension\Model\Feed\ProductFeed');
        $feed_push_response = $feed_obj->generateProductRequestData($access_token);
        $response['success'] = true;
        $response['$feed_push_response'] = $feed_push_response;
        $this->saveExternalBusinessId($external_business_id);
        return $response;
      }
    }catch (\Exception $e) {
      $response['success'] = false;
      $response['message'] = $e->getMessage();
      $this->_fbeHelper->logException($e);
      return $response;
    }
  }

  public function saveCatalogId($catalog_id) {
    if($catalog_id != null) {
      $this->_fbeHelper->saveConfig('fbe/catalog/id', $catalog_id);
      $this->_fbeHelper->log("Catalog id saved on instance --- ". $catalog_id);
    }
  }

  public function saveExternalBusinessId($external_business_id) {
    if($external_business_id != null) {
      $this->_fbeHelper->saveConfig('fbe/external/id', $external_business_id);
      $this->_fbeHelper->log("External business id saved on instance --- ". $external_business_id);
    }
  }
}
