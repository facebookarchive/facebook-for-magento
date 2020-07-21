<?php

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

class Fbpixel extends AbstractAjax {

  public function __construct(
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper) {
    parent::__construct($context, $resultJsonFactory, $fbeHelper);
  }

  // Yet to verify how to use the pii info, hence have commented the part of code.
  public function executeForJson() {
    $old_pixel_id = $this->_fbeHelper->getConfigValue('fbpixel/id');
    $response = array(
      'success' => false,
      'pixelId' => $old_pixel_id
      //'pixelUsePii' => $this->_fbeHelper->getConfigValue('fbpixel/pixel_use_pii')
    );
    $pixel_id = $this->getRequest()->getParam('pixelId');
    //$pixel_use_pii = $this->getRequest()->getParam('pixelUsePii');
    if ($pixel_id && $this->_fbeHelper->isValidFBID($pixel_id)) {
      $this->_fbeHelper->saveConfig('fbpixel/id', $pixel_id);
      $this->_fbeHelper->saveConfig('fbe/installed', true);
      // $this->_fbeHelper->saveConfig('fbpixel/pixel_use_pii', $pixel_use_pii === 'true'? '1' : '0');
      $response['success'] = true;
      $response['pixelId'] = $pixel_id;
      // $response['pixelUsePii'] = $pixel_use_pii;
      if ($old_pixel_id != $pixel_id) {
        $this->_fbeHelper->log(sprintf("Pixel id updated from %d to %d", $old_pixel_id, $pixel_id));
        $datetime = $this->_fbeHelper->createObject('\Magento\Framework\Stdlib\DateTime\DateTime');
        $this->_fbeHelper->saveConfig(
          'fbpixel/install_time',
          $datetime->gmtDate('Y-m-d H:i:s'));
      }
    }
    return $response;
  }
}
