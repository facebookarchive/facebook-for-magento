<?php

namespace Facebook\BusinessExtension\Logger;

class Logger extends \Monolog\Logger {

  public function __construct(
    \Magento\Framework\ObjectManagerInterface $objectManager) {
    $handler = $objectManager->create('Facebook\BusinessExtension\Logger\Handler');
    parent::__construct('FBE', array($handler));
  }

}
