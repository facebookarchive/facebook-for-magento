<?php

namespace Facebook\BusinessExtension\Block\Adminhtml;

class Setup extends Common {

  public function __construct(
    \Magento\Backend\Block\Template\Context $context,
    \Magento\Framework\Registry $registry,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    array $data = []) {
    parent::__construct($context, $registry, $fbeHelper, $data);
  }

  public function getPixelAjaxRoute() {
    return $this->_fbeHelper->getUrl('fbeadmin/ajax/fbpixel');
  }

  public function getAccessTokenAjaxRoute() {
    return $this->_fbeHelper->getUrl('fbeadmin/ajax/fbtoken');
  }

  public function getProfilesAjaxRoute() {
    return $this->_fbeHelper->getUrl('fbeadmin/ajax/fbprofiles');
  }

  public function getAAMSettingsRoute(){
    return $this->_fbeHelper->getUrl('fbeadmin/ajax/fbaamsettings');
  }

  public function fetchPixelId() {
    return $this->_fbeHelper->getConfigValue('fbpixel/id');
  }

  public function getExternalBusinessId() {
    return $this->_fbeHelper->getFBEExternalBusinessId();
  }

  public function getFeedPushAjaxRoute() {
    return $this->_fbeHelper->getUrl('fbeadmin/ajax/fbfeedpush');
  }

  public function getDeleteAssetIdsAjaxRoute() {
    return $this->_fbeHelper->getUrl('fbeadmin/ajax/fbdeleteasset');
  }

  public function getCurrencyCode() {
    return $this->_fbeHelper->getStoreCurrencyCode();
  }

  public function isFBEInstalled() {
    return $this->_fbeHelper->isFBEInstalled();
  }
}
