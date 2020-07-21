<?php

namespace Facebook\BusinessExtension\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Config extends AbstractDb {

  protected function _construct() {
    $this->_init('facebook_business_extension_config', 'config_key');
    $this->_isPkAutoIncrement = false;
  }
}
