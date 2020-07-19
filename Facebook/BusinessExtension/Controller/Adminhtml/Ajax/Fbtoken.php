<?php

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

class Fbtoken extends AbstractAjax {

  public function __construct(
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper) {
    parent::__construct($context, $resultJsonFactory, $fbeHelper);
  }

  public function executeForJson() {
    $old_access_token = $this->_fbeHelper->getConfigValue('fbaccess/token');
    $response = array(
      'success' => false,
      'accessToken' => $old_access_token
    );
    $access_token = $this->getRequest()->getParam('accessToken');
    if ($access_token) {
      $this->_fbeHelper->saveConfig('fbaccess/token', $access_token);
      $response['success'] = true;
      $response['accessToken'] = $access_token;
      if($old_access_token != $access_token) {
        $this->_fbeHelper->log("Updated Access token...");
        $datetime = $this->_fbeHelper->createObject('\Magento\Framework\Stdlib\DateTime\DateTime');
        $this->_fbeHelper->saveConfig(
          'fbaccesstoken/creation_time',
          $datetime->gmtDate('Y-m-d H:i:s'));
      }
    }
    return $response;
  }
}
