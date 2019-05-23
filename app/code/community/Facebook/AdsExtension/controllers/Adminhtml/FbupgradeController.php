<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (file_exists(__DIR__.'/../../lib/fb.php')) {
  include_once __DIR__.'/../../lib/fb.php';
} else {
  include_once __DIR__.'/../../../../Facebook_AdsExtension_lib_fb.php';
}

class Facebook_AdsExtension_Adminhtml_FbupgradeController
  extends Mage_Adminhtml_Controller_Action {
  const URL = 'https://api.github.com/repos/facebookincubator/facebook-for-magento/releases/latest';

  public function indexAction() {
    $this->ajaxAction();
  }

  private function ajaxSend($response) {
    $this->getResponse()->setHeader('Content-type', 'application/json');
    $this->getResponse()->setBody(
      Mage::helper('core')->jsonEncode($response));
  }

  public function ajaxAction() {
    try {
      $last_check_time =
        Mage::getStoreConfig('facebook_ads_toolbox/dia/last_upgrade_check');
      // Only check/show once a day.
      if ($last_check_time && time() - $last_check_time < 24*3600) {
        $this->sendAjaxReply(null, false, "Too soon to check for new version.");
        return;
      }
      Mage::getModel('core/config')->saveConfig(
        'facebook_ads_toolbox/dia/last_upgrade_check',
        time());

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, self::URL);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_USERAGENT, "curl");

      ob_start();
      curl_exec($ch);
      curl_close($ch);
      $lines = ob_get_contents();
      ob_end_clean();
      $json = json_decode($lines, true);

      if (!$json || !isset($json['tag_name'])) {
        $this->sendAjaxReply($json, false, $lines);
      }

      $version = FacebookAdsExtension::version();
      $name = $json['name'];
      $version_latest = $json['tag_name'];
      if (substr($version_latest,0,1) == 'v') {
        $version_latest = substr($version_latest, 1);
      }
      $this->sendAjaxReply($json, strcmp($version_latest, $version) > 0, $version_latest." vs ".$version);

    } catch (Exception $e) {
      FacebookAdsExtension::logException($e);
      $this->ajaxSend(array(
        'upgrade_needed' => 0,
        'error' => $e->getMessage(),
        'stack_trace' => $e->getTraceAsString(),
      ));
    }
  }

  private function sendAjaxReply($json, $upgrade, $lines = null) {
    $this->ajaxSend(array(
      'upgrade_needed' => $upgrade ? 1 : 0,
      'latest_version' => ($json && isset($json['tag_name'])) ?  $json['tag_name'] : '',
      'url' => ($json && isset($json['html_url'])) ? $json['html_url'] : '',
      'extra_info' => $lines,
    ));
  }

}
