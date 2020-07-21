<?php

namespace Facebook\BusinessExtension\Block\Pixel;

class ViewCategory extends Common {

  public function getCategory() {
    $category = $this->_registry->registry('current_category');
    if ($category) {
      return $this->escapeQuotes($category->getName());
    } else {
      return null;
    }
  }

  public function getEventToObserveName(){
    return 'facebook_businessextension_ssapi_view_category';
  }

}
