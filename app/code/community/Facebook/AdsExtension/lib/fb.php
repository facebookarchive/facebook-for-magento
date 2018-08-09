<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (!class_exists('FacebookAdsExtension', false)) {
  class FacebookAdsExtension {

    const LOGFILE = 'facebook_ads_extension.log';
    const FEED_LOGFILE = 'facebook_adstoolbox_product_feed.log';
    const FEED_EXCEPTION = 'facebook_product_feed_exception.log';
    const DEBUGMODE_LOGFILE = 'facebook_ads_extension_debug.log';
    public static $debug_mode = false;

    public static function version() {
      return Mage::getConfig()->getModuleConfig("Facebook_AdsExtension")->version;
    }

    public static function getMagentoVersion() {
      return Mage::getVersion();
    }

    private static function maybeUseHTTPS() {
       return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
    }

    public static function getBaseUrl() {
      // Use this function to get a base url respect to host protocol
      return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, self::maybeUseHTTPS());
    }

    public static function getBaseUrlMedia() {
      return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA, self::maybeUseHTTPS());
    }

    public static function getFeedGenUrl() {
      return
        FacebookAdsExtension::getBaseUrl().'facebookadstoolbox/productfeed/gen';
    }

    public static function getFeedPingUrl() {
      return
        FacebookAdsExtension::getBaseUrl().'facebookadstoolbox/productfeed/genPing';
    }

    public static function logException($e) {
       $msg = sprintf("Caught exception: %s.\n%s", $e->getMessage(), $e->getTraceAsString());
       self::log($msg);
    }

    public static function getLogs() {
      $log_file_path = Mage::getBaseDir('log').'/'.self::LOGFILE;
      return file_get_contents($log_file_path);
    }

    public static function setDebugMode($mode) {
        FacebookAdsExtension::$debug_mode = $mode;
    }

    public static function setErrorLogging() {
      register_shutdown_function ( function() {
        $errfile = "unknown file";
        $errstr  = "shutdown";
        $errno   = E_CORE_ERROR;
        $errline = 0;

        $error = error_get_last();

        if( $error !== NULL) {
          $errno   = $error["type"];
          $errfile = $error["file"];
          $errline = $error["line"];
          $errstr  = $error["message"];
          $log = $errno.":".$errstr." @ ".$errfile." L".$errline;
          Mage::log('ERROR'.$log, Zend_Log::INFO, FacebookAdsExtension::FEED_EXCEPTION);
          if (FacebookAdsExtension::$debug_mode) {
            Mage::log('ERROR'.$log, Zend_Log::EMERG, FacebookAdsExtension::DEBUGMODE_LOGFILE);
          }
        }
      } );
    }

    public static function getDebugModeLogs() {
      $log_file_path = Mage::getBaseDir('log').'/'.self::DEBUGMODE_LOGFILE;
      return file_get_contents($log_file_path);
    }

    public static function getFeedLogs() {
      $log_file_path = Mage::getBaseDir('log').'/'.self::FEED_LOGFILE;
      return file_get_contents($log_file_path);
    }

    public static function getFeedException() {
      $log_file_path = Mage::getBaseDir('log').'/'.self::FEED_EXCEPTION;
      return file_get_contents($log_file_path);
    }

    public static function log($msg) {
       Mage::log($msg, Zend_Log::INFO, self::LOGFILE);
    }

    public static function getDebugKey() {
      $debug_key = Mage::getStoreConfig('facebook_ads_toolbox/debug/id');
      if (!$debug_key) {
        $debug_key = sha1(rand().",".rand().",".time());
        Mage::getConfig()->saveConfig(
          'facebook_ads_toolbox/debug/id',
          $debug_key);
      }
      return $debug_key;
    }

    public static function getStoreName() {
      $frontendName = Mage::app()->getStore()->getFrontendName();
      if ($frontendName !== 'Default') {
        return $frontendName;
      }
      $defaultStoreId = self::getDefaultStoreID();
      $defaultStoreName = Mage::getModel('core/store')
        ->load($defaultStoreId)->getGroup()->getName();
      $escapeStrings = array("\r", "\n", "&nbsp;", "\t");
      $defaultStoreName =
        trim(str_replace($escapeStrings, ' ', $defaultStoreName));
      if (!$defaultStoreName) {
        $defaultStoreName = Mage::app()->getWebsite(true)->getName();
        $defaultStoreName =
          trim(str_replace($escapeStrings, ' ', $defaultStoreName));
      }
      if ($defaultStoreName && $defaultStoreName !== 'Main Website Store'
        && $defaultStoreName !== 'Main Store'
        && $defaultStoreName !== 'Main Website') {
        return $defaultStoreName;
      }
      return parse_url(self::getBaseUrl(), PHP_URL_HOST);
    }

    public static function getDefaultStoreID($validity_check = false) {
      $store_id = Mage::getStoreConfig('facebook_ads_toolbox/fbstore/id');
      if (!$validity_check && $store_id) {
        return $store_id;
      }

      try {
        $valid_store_id = false;
        // Check that store_id is valid, if a store gets deleted, we should_log
        // change the store back to the default store
        if ($store_id) {
          $stores = Mage::app()->getStores(true);

          foreach ($stores as $store) {
            if ($store_id === $store->getId()) {
              $valid_store_id = true;
              break;
            }
          }
          // If the store id is invalid, save the default id
          if (!$valid_store_id) {
            $store_id =
              Mage::app()
                ->getWebsite(true)
                ->getDefaultGroup()
                ->getDefaultStoreId();
            Mage::getModel('core/config')->saveConfig(
              'facebook_ads_toolbox/fbstore/id',
              $store_id);
          }
        }

        return is_numeric($store_id)
          ? $store_id
          : Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
      } catch (Exception $e) {
        FacebookAdsExtension::log('Failed getting store ID, returning default');
        return ($store_id) ?
          $store_id :
          Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
      }
    }

    public static function getTotalVisibleProducts($store_id) {
      if ($store_id === null || !is_numeric($store_id)) {
        $store_id = self::getDefaultStoreId();
      }

      return Mage::getModel('catalog/product')->getCollection()
        ->addStoreFilter($store_id)
        ->addAttributeToFilter('visibility',
            array(
              'neq' =>
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
            )
        )
        ->addAttributeToFilter('status',
            array(
              'eq' =>
                Mage_Catalog_Model_Product_Status::STATUS_ENABLED
            ))
        ->getSize();
    }

    public static $fbTimezones =  array(
      "africa_accra" => 59,
      "africa_cairo" => 53,
      "africa_casablanca" => 86,
      "africa_johannesburg" => 141,
      "africa_lagos" => 96,
      "africa_nairobi" => 78,
      "africa_tunis" => 133,
      "america_anchorage" => 4,
      "america_argentina_buenos_aires" => 10,
      "america_argentina_salta" => 11,
      "america_argentina_san_luis" => 9,
      "america_asuncion" => 111,
      "america_atikokan" => 33,
      "america_belem" => 24,
      "america_blanc_sablon" => 36,
      "america_bogota" => 43,
      "america_campo_grande" => 23,
      "america_caracas" => 139,
      "america_chicago" => 6,
      "america_costa_rica" => 44,
      "america_dawson" => 27,
      "america_dawson_creek" => 29,
      "america_denver" => 2,
      "america_edmonton" => 30,
      "america_el_salvador" => 131,
      "america_guatemala" => 61,
      "america_guayaquil" => 51,
      "america_halifax" => 37,
      "america_hermosillo" => 92,
      "america_iqaluit" => 34,
      "america_jamaica" => 75,
      "america_la_paz" => 21,
      "america_lima" => 103,
      "america_los_angeles" => 1,
      "america_managua" => 97,
      "america_mazatlan" => 93,
      "america_mexico_city" => 94,
      "america_montevideo" => 138,
      "america_nassau" => 26,
      "america_new_york" => 7,
      "america_noronha" => 22,
      "america_panama" => 102,
      "america_phoenix" => 5,
      "america_port_of_spain" => 135,
      "america_puerto_rico" => 107,
      "america_rainy_river" => 31,
      "america_regina" => 32,
      "america_santiago" => 41,
      "america_santo_domingo" => 49,
      "america_sao_paulo" => 25,
      "america_st_johns" => 38,
      "america_tegucigalpa" => 63,
      "america_tijuana" => 91,
      "america_toronto" => 35,
      "america_vancouver" => 28,
      "asia_amman" => 76,
      "asia_baghdad" => 72,
      "asia_bahrain" => 20,
      "asia_bangkok" => 132,
      "asia_beirut" => 81,
      "asia_colombo" => 82,
      "asia_dhaka" => 17,
      "asia_dubai" => 8,
      "asia_gaza" => 108,
      "asia_ho_chi_minh" => 140,
      "asia_hong_kong" => 62,
      "asia_irkutsk" => 121,
      "asia_jakarta" => 66,
      "asia_jayapura" => 68,
      "asia_jerusalem" => 70,
      "asia_kamchatka" => 125,
      "asia_karachi" => 105,
      "asia_kolkata" => 71,
      "asia_krasnoyarsk" => 120,
      "asia_kuala_lumpur" => 95,
      "asia_kuwait" => 80,
      "asia_magadan" => 124,
      "asia_makassar" => 67,
      "asia_manila" => 104,
      "asia_muscat" => 102,
      "asia_nicosia" => 45,
      "asia_novosibirsk" => 120,
      "asia_omsk" => 119,
      "asia_qatar" => 112,
      "asia_riyadh" => 126,
      "asia_seoul" => 79,
      "asia_shanghai" => 42,
      "asia_singapore" => 128,
      "asia_taipei" => 136,
      "asia_tokyo" => 77,
      "asia_vladivostok" => 123,
      "asia_yakutsk" => 122,
      "asia_yekaterinburg" => 118,
      "atlantic_azores" => 109,
      "atlantic_canary" => 54,
      "atlantic_reykjavik" => 73,
      "australia_broken_hill" => 14,
      "australia_perth" => 13,
      "australia_sydney" => 15,
      "europe_amsterdam" => 98,
      "europe_athens" => 60,
      "europe_belgrade" => 114,
      "europe_berlin" => 47,
      "europe_bratislava" => 130,
      "europe_brussels" => 18,
      "europe_bucharest" => 113,
      "europe_budapest" => 65,
      "europe_copenhagen" => 48,
      "europe_dublin" => 69,
      "europe_helsinki" => 56,
      "europe_istanbul" => 134,
      "europe_kaliningrad" => 115,
      "europe_kiev" => 137,
      "europe_lisbon" => 110,
      "europe_ljubljana" => 129,
      "europe_london" => 58,
      "europe_luxembourg" => 84,
      "europe_madrid" => 55,
      "europe_malta" => 88,
      "europe_moscow" => 116,
      "europe_oslo" => 99,
      "europe_paris" => 57,
      "europe_prague" => 46,
      "europe_riga" => 85,
      "europe_rome" => 74,
      "europe_samara" => 117,
      "europe_sarajevo" => 16,
      "europe_skopje" => 87,
      "europe_sofia" => 19,
      "europe_stockholm" => 127,
      "europe_tallinn" => 52,
      "europe_vienna" => 12,
      "europe_vilnius" => 83,
      "europe_warsaw" => 106,
      "europe_zagreb" => 64,
      "europe_zurich" => 39,
      "indian_maldives" => 90,
      "indian_mauritius" => 89,
      "num_timezones" => 142,
      "pacific_auckland" => 100,
      "pacific_easter" => 40,
      "pacific_galapagos" => 50,
      "pacific_honolulu" => 3,
      "unknown" => 0
    );

    public static function determineFbTimeZone($magentoTimezone) {
      $fb_timezone = str_replace("/", "_", strtolower($magentoTimezone));
      return isset(self::$fbTimezones[$fb_timezone]) ?
        self::$fbTimezones[$fb_timezone] :
        self::$fbTimezones['unknown'];
    }


  }
}
