<?php

namespace Facebook\BusinessExtension\Block\Adminhtml;

class Common extends \Magento\Backend\Block\Template {

  protected $_registry;
  protected $_fbeHelper;

  public function __construct(
    \Magento\Backend\Block\Template\Context $context,
    \Magento\Framework\Registry $registry,
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper,
    array $data = []) {
    $this->_registry = $registry;
    $this->_fbeHelper = $fbeHelper;
    parent::__construct($context, $data);
  }
}
