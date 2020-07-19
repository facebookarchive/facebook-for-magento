<?php

namespace Facebook\BusinessExtension\Controller\Adminhtml\Ajax;

class Fbaamsettings extends AbstractAjax {

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
      $settings = $this->_fbeHelper->fetchAAMSettings($pixelId);
      if($settings){
        $settingsAsArray = array(
          'enableAutomaticMatching' => $settings->getEnableAutomaticMatching(),
          'enabledAutomaticMatchingFields' => $settings->getEnabledAutomaticMatchingFields(),
          'pixelId' => $settings->getPixelId(),
        );
        $settingsAsString = json_encode($settingsAsArray);
        $this->_fbeHelper->saveConfig('fbpixel/aam_settings', $settingsAsString);
        $response['success'] = true;
        $response['settings'] = $settingsAsString;
      }
    }
    return $response;
  }
}
