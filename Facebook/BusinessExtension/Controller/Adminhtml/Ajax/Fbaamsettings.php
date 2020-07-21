<?php

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

class FbAAMSettings extends AbstractAjax {

  public function __construct(
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper) {
    parent::__construct($context, $resultJsonFactory, $fbeHelper);
  }

  public function executeForJson() {
    $response = array(
      'success' => false,
      'settings' => null,
    );
    $pixelId = $this->getRequest()->getParam('pixelId');
    if ($pixelId) {
      $settingsAsString = $this->_fbeHelper->fetchAndSaveAAMSettings($pixelId);
      if($settingsAsString){
        $response['success'] = true;
        $response['settings'] = $settingsAsString;
      }
    }
    return $response;
  }
}
