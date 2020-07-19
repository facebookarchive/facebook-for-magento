<?php

namespace Facebook\BusinessExtension\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

class FBEHelper extends AbstractHelper {

  const LOGFILE = 'facebook_business_extension.log';
  const FEED_LOGFILE = 'facebook_adstoolbox_product_feed.log';
  const FEED_EXCEPTION = 'facebook_product_feed_exception.log';

  const MAIN_WEBSITE_STORE = 'Main Website Store';
  const MAIN_STORE = 'Main Store';
  const MAIN_WEBSITE = 'Main Website';

  const FB_GRAPH_BASE_URL = "https://graph.facebook.com/";

  const DELETE_SUCCESS_MESSAGE = "You have successfully deleted Facebook Business Extension.
    The pixel installed on your website is now deleted.";

  const DELETE_FAILURE_MESSAGE = "There was a problem deleting the connection.
      Please try again.";

  const CURRENT_API_VERSION = "v6.0";

  /**
   * @var \Magento\Framework\ObjectManagerInterface
   */
  protected $_objectManager;
  /**
   * @var \Magento\Store\Model\StoreManagerInterface
   */
  protected $_storeManager;
  /**
   * @var \Facebook\BusinessExtension\Model\ConfigFactory
   */
  protected $_configFactory;
  /**
   * @var \Facebook\BusinessExtension\Logger\Logger
   */
  protected $_logger;
  /**
   * @var \Magento\Framework\App\Filesystem\DirectoryList
   */
  protected $_directoryList;
  /**
   * @var \Magento\Framework\HTTP\Client\Curl
   */
  protected $_curl;
  /**
   * @var \Magento\Framework\App\ResourceConnection
  */
  protected $_resourceConnection;

  public function __construct(
    Context $context,
    ObjectManagerInterface $objectManager,
    \Facebook\BusinessExtension\Model\ConfigFactory $configFactory,
    \Facebook\BusinessExtension\Logger\Logger $logger,
    \Magento\Framework\App\Filesystem\DirectoryList $directorylist,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Magento\Framework\HTTP\Client\Curl $curl,
    \Magento\Framework\App\ResourceConnection $resourceConnection) {
    parent::__construct($context);
    $this->_objectManager = $objectManager;
    $this->_storeManager = $storeManager;
    $this->_configFactory = $configFactory;
    $this->_logger = $logger;
    $this->_directoryList = $directorylist;
    $this->_curl = $curl;
    $this->_resourceConnection = $resourceConnection;
  }


  public function isS2SEnabled() {
    return false;
  }

  public function getPixelID() {
    return $this->getConfigValue('fbpixel/id');
  }

  public function getAccessToken() {
    return $this->getConfigValue('fbaccess/token');
  }

  public function getMagentoVersion() {
    return $this->_objectManager->get(
      'Magento\Framework\App\ProductMetadataInterface')->getVersion();
  }

  public function getPluginVersion() {
    return '0.0.1';
  }

  public function getUrl($partialURL) {
    $urlinterface = $this->getObject('\Magento\Backend\Model\UrlInterface');
    return $urlinterface->getUrl($partialURL);
  }

  public function getBaseUrlMedia() {
    return $this->_storeManager->getStore()->getBaseUrl(
      UrlInterface::URL_TYPE_MEDIA,
      $this->maybeUseHTTPS());
  }

  private function maybeUseHTTPS() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
  }

  public function createObject($fullClassName, array $arguments = []) {
    return $this->_objectManager->create($fullClassName, $arguments);
  }

  public function getObject($fullClassName) {
    return $this->_objectManager->get($fullClassName);
  }

  public static function isValidFBID($id) {
    return preg_match("/^\d{1,20}$/", $id) === 1;
  }

  public function getStore($storeId = null) {
    return $this->_storeManager->getStore($storeId);
  }

  public function getBaseUrl() {
    // Use this function to get a base url respect to host protocol
    return $this->getStore()->getBaseUrl(
      UrlInterface::URL_TYPE_WEB,
      $this->maybeUseHTTPS());
  }


  public function saveConfig($configKey, $configValue) {
    try {
      $config_row = $this->_configFactory->create()->load($configKey);
      if ($config_row->getData('config_key')) {
        $config_row->setData('config_value', $configValue);
        $config_row->setData('update_time', time());
      } else {
        $t = time();
        $config_row->setData('config_key', $configKey);
        $config_row->setData('config_value', $configValue);
        $config_row->setData('creation_time', $t);
        $config_row->setData('update_time', $t);
      }
      $config_row->save();
    } catch(\Exception $e) {
      $this->logException($e);
    }
  }

  public function getCurrentStorefrontLocaleCode() {
    return $this->_objectManager->get('\Magento\Store\Api\Data\StoreInterface')->getLocaleCode();
  }

  public function getDefaultStoreID($validity_check = false) {
    $store_id = $this->getConfigValue('fbstore/id');
    if (!$validity_check && $store_id) {
      return $store_id;
    }

    try {
      $valid_store_id = false;
      // Check that store_id is valid, if a store gets deleted, we should_log
      // change the store back to the default store
      if ($store_id) {
        $stores = $this->getStores(true);

        foreach ($stores as $store) {
          if ($store_id === $store->getId()) {
            $valid_store_id = true;
            break;
          }
        }
        // If the store id is invalid, save the default id
        if (!$valid_store_id) {
          $store_id = $this->getStore()->getId();
          $this->saveConfig('fbstore/id', $store_id);
        }
      }

      return is_numeric($store_id)
          ? $store_id
          : $this->getStore()->getId();
    } catch (\Exception $e) {
       $this->log('Failed getting store ID, returning default');
       $this->logException($e);
      return ($store_id)
          ? $store_id
          : $this->getStore()->getId();
    }
  }

  public function getStores($withDefault = false, $codeKey = false) {
    return $this->_storeManager->getStores($withDefault, $codeKey);
  }

  public function getConfigValue($configKey) {
    try {
      $config_row = $this->_configFactory->create()->load($configKey);
    } catch(\Exception $e) {
       $this->logException($e);
      return null;
    }
    return $config_row ? $config_row->getConfigValue() : null;
  }

  public function makeHttpRequest($request_params, $access_token = null) {
    $response = null;
    if ($access_token == null) {
      $access_token = $this->getConfigValue('fbaccess/token');
    }
    try {
      $url = $this->getCatalogBatchAPI($access_token);
      $params = array(
        'access_token' => $access_token,
        'requests' => $request_params
      );
      $this->_curl->post($url, $params);
      $response = $this->_curl->getBody();
    } catch (\Exception $e) {
      $this->logException($e);
    }
    return $response;
  }

  public function getFBEExternalBusinessId() {
    $stored_external_id = $this->getConfigValue('fbe/external/id');
    if($stored_external_id) {
      return $stored_external_id;
    }
    $store_id = $this->_storeManager->getStore()->getId();
    $this->log("Store id---". $store_id);
    return  uniqid('fbe_magento_'.$store_id.'_');
  }

  public function getStoreName() {
    $frontendName = $this->getStore()->getFrontendName();
    if ($frontendName !== 'Default') {
      return $frontendName;
    }
    $defaultStoreId = $this->getDefaultStoreID();
    $defaultStoreName = $this->getStore($defaultStoreId)->getGroup()->getName();
    $escapeStrings = array('\r', '\n', '&nbsp;', '\t');
    $defaultStoreName =
      trim(str_replace($escapeStrings, ' ', $defaultStoreName));
    if (!$defaultStoreName) {
      $defaultStoreName = $this->getStore()->getName();
      $defaultStoreName =
        trim(str_replace($escapeStrings, ' ', $defaultStoreName));
    }
    if ($defaultStoreName && $defaultStoreName !== self::MAIN_WEBSITE_STORE
      && $defaultStoreName !== self::MAIN_STORE
      && $defaultStoreName !== self::MAIN_WEBSITE) {
      return $defaultStoreName;
    }
    return parse_url(self::getBaseUrl(), PHP_URL_HOST);
  }

  public function log($info) {
    $this->_logger->info($info);
  }

  public function logException($e) {
    $this->_logger->error($e->getMessage());
    $this->_logger->error($e->getTraceAsString());
    $this->_logger->error($e);
  }

  public function setErrorLogging() {
    register_shutdown_function(function() {
      $errfile = 'unknown file';
      $errstr  = 'shutdown';
      $errno   = E_CORE_ERROR;
      $errline = 0;
      $error = error_get_last();
      if ($error !== null) {
        $errno   = $error['type'];
        $errfile = $error['file'];
        $errline = $error['line'];
        $errstr  = $error['message'];
        $log = $errno.':'.$errstr.' @ '.$errfile.' L'.$errline;
        $this->_logger->error('ERROR '.$log);
        if (self::$debug_mode) {
          $this->_logger->error('ERROR '.$log);
        }
      }
    });
  }

  public function getLogs() {
    $log_file_path = $this->getDirectoryPath('log').'/'.self::LOGFILE;
    return file_get_contents($log_file_path);
  }

  public function getFeedLogs() {
    $log_file_path = $this->getDirectoryPath('log').'/'.self::FEED_LOGFILE;
    return file_get_contents($log_file_path);
  }

  public function getFeedException() {
    $log_file_path = $this->getDirectoryPath('log').'/'.self::FEED_EXCEPTION;
    return file_get_contents($log_file_path);
  }

  public function getDirectoryPath($type) {
    return $this->_directoryList->getPath($type);
  }

  public function getAPIVersion($access_token) {
    $api_version = null;
    try {
      $config_row = $this->_configFactory->create()->load('fb/api/version');
      $api_version = $config_row ? $config_row->getConfigValue() : null;
      $this->log("Current api version : ".$api_version);

      $version_last_update = $config_row ? $config_row->getUpdateTime() : null;
      $this->log("Version last update: ".$version_last_update);

      $is_updated_version = $this->isUpdatedVersion($version_last_update);
      if ($api_version && $is_updated_version) {
        $this->log("Returning the version already stored in db : ".$api_version);
        return $api_version;
      }

      $this->_curl->addHeader("Authorization", "Bearer " . $access_token);
      $this->_curl->get(self::FB_GRAPH_BASE_URL.'api_version');
      $this->log("The API call: ".self::FB_GRAPH_BASE_URL.'api_version');

      $response = $this->_curl->getBody();
      $this->log("The API reponse : ".json_encode($response));
      $decode_response = json_decode($response);
      $api_version = $decode_response->api_version;
      $this->log("The version fetched via API call: ".$api_version);

      $this->saveConfig('fb/api/version', $api_version);
    }catch(\Exception $e) {
      $this->log("Failed to fetch latest api version with error ".$e->getMessage());
    }
    return $api_version ? $api_version : self::CURRENT_API_VERSION;
  }

  public function logPixelEvent($pixel_id, $pixel_event) {
    $this->log($pixel_event. " event fired for Pixel id : ".$pixel_id);
  }

  public function deleteConfigKeys() {
    $response = array();
    $response['success'] = false;
    try {
      $connection= $this->_resourceConnection->getConnection();
      $facebook_config = $this->_resourceConnection->getTableName('facebook_business_extension_config');
      $sql = "DELETE  FROM $facebook_config";
      $connection->query($sql);
      $response['success'] = true;
      $response['message'] = self::DELETE_SUCCESS_MESSAGE;
    }catch (\Exception $e) {
      $this->log($e->getMessage());
      $response['error_message'] = self::DELETE_FAILURE_MESSAGE;
    }
    return $response;
  }

  public function isUpdatedVersion($version_last_update) {
    if(!$version_last_update) {
      return null;
    }
    $months_since_last_update = 3;
    try {
      $datetime1 = new \DateTime($version_last_update);
      $datetime2 = new \DateTime();
      $interval = date_diff($datetime1, $datetime2);
      $interval_vars = get_object_vars ( $interval );
      $months_since_last_update = $interval_vars['m'];
      $this->log("Months since last update : ".$months_since_last_update);
    }catch (\Exception $e) {
      $this->log($e->getMessage());
    }
    // Since the previous version is valid for 3 months,
    // I will check to see for the gap to be only 2 months to be safe.
    return $months_since_last_update <= 2;
  }

  public function getCatalogBatchAPI($access_token) {
    $catalog_id = $this->getConfigValue('fbe/catalog/id');;
    $external_business_id = $this->getFBEExternalBusinessId();
    if ($catalog_id != null) {
      $catalog_path = "/" . $catalog_id . "/batch";
    } else {
      $catalog_path = "/fbe_catalog/batch?fbe_external_business_id=" .
        $external_business_id;
    }
    $catalog_batch_api = self::FB_GRAPH_BASE_URL .
      $this->getAPIVersion($access_token) .
      $catalog_path;
    $this->log("Catalog Batch API - " . $catalog_batch_api);
    return $catalog_batch_api;
  }

  public function getStoreCurrencyCode() {
    $store_id = $this->getDefaultStoreID();
    return $this->getStore($store_id)->getCurrentCurrencyCode();
  }

  public function isFBEInstalled() {
    $is_fbe_installed = $this->getConfigValue('fbe/installed');
    if($is_fbe_installed) {
      return 'true';
    }
    return 'false';
  }

  public function fetchAAMSettings($pixelId){
    return AdsPixelSettings::buildFromPixelId($pixelId);
  }

  public function getAAMSettings(){
    $settingsAsString = $this->getConfigValue('fbpixel/aam_settings');
    if( $settingsAsString ){
      $settingsAsArray = json_decode($settingsAsString, true);
      if($settingsAsArray){
        $settings = new AdsPixelSettings();
        $settings->setPixelId($settingsAsArray['pixelId']);
        $settings->setEnableAutomaticMatching($settingsAsArray['enableAutomaticMatching']);
        $settings->setEnabledAutomaticMatchingFields($settingsAsArray['enabledAutomaticMatchingFields']);
        return $settings;
      }
    }
    return null;
  }
}
