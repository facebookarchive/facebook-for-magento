<?php

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

abstract class AbstractAjax extends \Magento\Backend\App\Action {
  /**
   * @var \Magento\Framework\Controller\Result\JsonFactory
   */
  protected $_resultJsonFactory;

  protected $_fbeHelper;

  public function __construct(
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper) {
    parent::__construct($context);
    $this->_resultJsonFactory = $resultJsonFactory;
    $this->_fbeHelper = $fbeHelper;
  }

  abstract protected function executeForJson();

  public function execute() {
    $result = $this->_resultJsonFactory->create();
    // TODO : Move all String objects to constants.
    $admin_session = $this->_fbeHelper
      ->createObject('Magento\Security\Model\AdminSessionsManager')
      ->getCurrentSession();
    if (!$admin_session && $admin_session->getStatus() != 1) {
      throw new \Exception('Oops, this endpoint is for logged in admin and ajax only!');
    } else {
      try {
        $json = $this->executeForJson();
        return $result->setData($json);
      } catch (\Exception $e) {
        // Uncomment once the logger is added
        // $this->_fbeHelper->logException($e);
        throw new Exception(
          'Oops, there was error while processing your request.' .
          ' Please contact admin for more details.'
        );
      }
    }
  }
}
