<?php

namespace Facebook\BusinessExtension\Model\ResourceModel\Config;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection {

  protected function _construct() {
    $this->_init(
        'Facebook\BusinessExtension\Model\Config',
        'Facebook\BusinessExtension\Model\ResourceModel\Config');
  }
}
