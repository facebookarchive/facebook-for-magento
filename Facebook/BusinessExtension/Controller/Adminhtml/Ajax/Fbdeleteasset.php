<?php


namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;


class Fbdeleteasset extends AbstractAjax {

  public function __construct(
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper) {
    parent::__construct($context, $resultJsonFactory, $fbeHelper);
  }

  public function executeForJson() {
    return $this->_fbeHelper->deleteConfigKeys();
  }
}