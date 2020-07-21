<?php

namespace Facebook\BusinessExtension\Cron;

class AAMSettingsCron{

  protected $_fbeHelper;

  public function __construct(
    \Facebook\BusinessExtension\Helper\FBEHelper $fbeHelper
  ){
    $this->_fbeHelper = $fbeHelper;
  }

  public function execute(){
    $pixelId = $this->_fbeHelper->getPixelID();
    $this->_fbeHelper->log('In CronJob for fetching AAM Settings for Pixel: ' . $pixelId);
    $settingsAsString = null;
    if($pixelId){
      $settingsAsString = $this->_fbeHelper->fetchAndSaveAAMSettings($pixelId);
      if($settingsAsString){
        $this->_fbeHelper->log('Saving settings '.$settingsAsString);
      }
      else{
        $this->_fbeHelper->log('Error saving settings');
      }
    }
    return $settingsAsString;
  }
}
