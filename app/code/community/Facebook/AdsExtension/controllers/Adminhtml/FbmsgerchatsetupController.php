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

class Facebook_AdsExtension_Adminhtml_FbmsgerchatsetupController
  extends Mage_Adminhtml_Controller_Action {

  const PREFIX = 'facebook_ads_toolbox/messengerchat/';

  public function indexAction() {
    $this->loadLayout();
    $this->renderLayout();
  }

  public function ajaxAction() {
    if (Mage::app()->getRequest()->isAjax()) {
      $response = array(
        'success' => false,
      );

      try {
        $request = $this->getRequest();

        $is_messenger_chat_plugin_enabled =
          $request->getParam('is_messenger_chat_plugin_enabled');
        if ($is_messenger_chat_plugin_enabled) {
          Mage::getModel('core/config')->saveConfig(
            self::PREFIX.'enabled',
            $is_messenger_chat_plugin_enabled === 'true' ? '1' : '0');
        }

        $facebook_jssdk_version = $request->getParam('facebook_jssdk_version');
        if ($facebook_jssdk_version) {
          Mage::getModel('core/config')->saveConfig(
            'facebook_ads_toolbox/fbjssdk/version',
            $facebook_jssdk_version);
        }

        $page_id = $request->getParam('page_id');
        if ($page_id) {
          Mage::getModel('core/config')->saveConfig(
            self::PREFIX.'pageid',
            $page_id);
        }

        $customization = $request->getParam('customization');
        if ($customization) {
          $customization_obj = json_decode($customization);
          if ($customization_obj) {
            if (isset($customization_obj->greetingTextCode)) {
              Mage::getModel('core/config')->saveConfig(
                self::PREFIX.'greeting_text_code',
                $customization_obj->greetingTextCode);
            }

            if (isset($customization_obj->locale)) {
              Mage::getModel('core/config')->saveConfig(
                self::PREFIX.'locale',
                $customization_obj->locale);
            }

            if (isset($customization_obj->themeColorCode)) {
              Mage::getModel('core/config')->saveConfig(
                self::PREFIX.'theme_color_code',
                $customization_obj->themeColorCode);
            }
          }
        }

        $response['success'] = true;
        FacebookAdsExtension::log("Messenger chat setup request received and saved!");
      } catch (Exception $e) {
        $response['success'] = false;
        FacebookAdsExtension::logException($e);
      }

      $this->getResponse()->setHeader('Content-type', 'application/json');
      $this->getResponse()
           ->setBody(Mage::helper('core')
           ->jsonEncode($response));
     } else {
      Mage::app()->getResponse()->setRedirect(
        Mage::helper('adminhtml')->getUrl(
          'adminhtml/fbmsgerchatsetup/index'));
    }
  }

}
