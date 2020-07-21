<?php

namespace Facebook\BusinessExtension\Block\Pixel;

class Search extends Common {

  public function getSearchQuery() {
    return htmlspecialchars(
      $this->getRequest()->getParam('q'),
      ENT_QUOTES,
      'UTF-8');
  }

  public function getEventToObserveName(){
    return 'facebook_businessextension_ssapi_search';
  }
}
