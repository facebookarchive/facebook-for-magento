<?php

namespace Facebook\BusinessExtension\Model;

use Magento\Framework\Model\AbstractModel;

class Config extends AbstractModel
  implements \Magento\Framework\DataObject\IdentityInterface {

  const CACHE_TAG = 'facebook_business_extension';

  protected function _construct() {
    $this->_init('Facebook\BusinessExtension\Model\ResourceModel\Config');
  }

  public function getIdentities() {
    return [self::CACHE_TAG . '_' . $this->getConfigKey()];
  }

}
