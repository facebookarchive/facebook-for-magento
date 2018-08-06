<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (file_exists(__DIR__.'/FBProductFeed.php')) {
  include_once 'FBProductFeed.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FBProductFeed.php';
}

if (file_exists(__DIR__.'/FBProductFeedTSV.php')) {
  include_once 'FBProductFeedTSV.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FBProductFeedTSV.php';
}

if (file_exists(__DIR__.'/FBProductFeedXML.php')) {
  include_once 'FBProductFeedXML.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FBProductFeedXML.php';
}

if (file_exists(__DIR__.'/FBProductFeedSamples.php')) {
  include_once 'FBProductFeedSamples.php';
} else {
  include_once 'Facebook_AdsExtension_Model_FBProductFeedSamples.php';
}

class Facebook_AdsExtension_Model_Observer {

  public function addToCart($observer) {
    if (!session_id()) { return; }
    $product = $observer->getProduct();
    $productId = $product->getId();

    // If we added an invisible product, add the parent instead otherwise
    // the add to cart pixel fire won't match.
    if (
      $product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE &&
      $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
    ) {
      $parentIds = Mage::getModel('catalog/product_type_configurable')
        ->getParentIdsByChild($productId);
      if (!empty($parentIds) && is_array($parentIds) && $parentIds[0]) {
        $productId = $parentIds[0];
      }
    }

    $session = Mage::getSingleton("core/session",  array("name"=>"frontend"));
    $addToCartArray = $session->getData("fbms_add_to_cart") ?: array();
    $addToCartArray[] = $productId;
    $session->setData("fbms_add_to_cart", $addToCartArray);
  }

  public function estimateFeedGenerationTime() {
    $feed = self::getFeedObject();

    // Estimate = MAX (Appx Time to Gen 500 Products + 30 , Last Runtime + 20)
    $time_estimate = $feed->estimateGenerationTime();
    $time_previous_avg =
      Mage::getStoreConfig('facebook_ads_toolbox/dia/feed/runtime_avg') + 20.0;
    return max($time_estimate, $time_previous_avg);
  }

  private function _createFileLockForFeedPath($feedpath) {
    $lock_path = $feedpath.'.lck';
    $fp = fopen($lock_path, 'w');
    fclose($fp);
  }

  private function _removeFileLockForFeedPath($feedpath) {
    $lock_path = $feedpath.'.lck';
    unlink($lock_path);
  }

  private function _isFileStaleLockedForFeedPath($feedpath) {
    $lock_path = $feedpath.'.lck';
    if (file_exists($lock_path)) {
      if (FBProductFeed::fileIsStale($lock_path)) {
        return 'stale_lock';
      } else {
        return 'fresh_lock';
      }
    } else {
      return 'no_lock';
    }
  }

  private static function getFeedObject() {
    $supportzip = extension_loaded('zlib');
    $format = Mage::getStoreConfig(
      FBProductFeed::PATH_FACEBOOK_ADSEXTENSION_FEED_GENERATION_FORMAT
    ) ?: 'TSV';
    $feed = ($format === 'TSV') ? new FBProductFeedTSV() :
                                  new FBProductFeedXML();
    return $feed;
  }

  private static function checkFeedExists() {
    return file_exists(self::getFeedObject()->getFullPath());
  }

  public function internalGenerateFBProductFeed(
    $throwException = false,
    $checkCache = true
  ) {
    self::maybeSetPixelInstallTime();

    FBProductFeed::log('feed generation start...');
    $time_start = time();
    $supportzip = extension_loaded('zlib');
    $feed = self::getFeedObject();
    $feed_target_file_path = $feed->getTargetFilePath($supportzip);
    $format = ($feed instanceof FBProductFeedTSV) ? 'TSV' : 'XML';

    if ($checkCache) {
      $isstale = $feed->cacheIsStale($supportzip);
      $lock_status =
        $this->_isFileStaleLockedForFeedPath($feed_target_file_path);
      if (($lock_status ==  'no_lock') && !$isstale) {
        $time_end = time();
        FBProductFeed::log(
          sprintf(
            'feed files are fresh and complete, skip generation, '.
            'time used: %d seconds',
            ($time_end - $time_start)));
        return array($format, $feed, $supportzip);
      } else if ($lock_status == 'fresh_lock') {
        if ($throwException) {
          throw new Exception(
            sprintf('Lock is fresh, generation must be in process.')
          );
        } else {
          FBProductFeed::log(
            sprintf('Lock is fresh, generation must be in process.')
          );
          return;
        }
      }
      // no_lock & stale feed, or stale_lock, we will regen the feed
    }

    try {
      $this->_createFileLockForFeedPath($feed_target_file_path);
      $feed->save();
      if ($supportzip) {
        $feed->saveGZip();
      }
    } catch (\Exception $e) {
      FBProductFeed::log(sprintf(
        'Caught exception: %s. %s', $e->getMessage(), $e->getTraceAsString()
      ));
      if ($throwException) {
        throw $e;
      }
      return;
    }
    $this->_removeFileLockForFeedPath($feed_target_file_path);

    $time_end = time();
    $feed_gen_time = ($time_end - $time_start);
    FBProductFeed::log(
      sprintf(
        'feed generation finished, time used: %d seconds',
        $feed_gen_time));

    // Update feed generation online time estimate w/ 25% decay.
    $old_feed_gen_time =
      Mage::getStoreConfig('facebook_ads_toolbox/dia/feed/runtime_avg');
    if ($feed_gen_time < $old_feed_gen_time) {
      $feed_gen_time = $feed_gen_time * 0.25 + $old_feed_gen_time * 0.75;
    }

    Mage::getModel('core/config')->saveConfig(
      'facebook_ads_toolbox/dia/feed/runtime_avg',
      $feed_gen_time
    );
    return array($format, $feed, $supportzip);
  }

  public function generateFBProductFeed($schedule) {
    $this->internalGenerateFBProductFeed();
  }

  public function generateFacebookProductSamples() {
    $feed = new FBProductFeedSamples();
    return $feed->generate();
  }

  public function disableCache($observer) {
    $controller_name =
      $observer->getEvent()->getControllerAction()->getFullActionName();

    // Clear cache for FB controllers.
    if (strpos($controller_name, 'adminhtml_fb') !== false) {
      Mage::app()->getCacheInstance()->cleanType('config');
    }
  }

  public static function checkFeedWriteError() {
    try {
      if (self::checkFeedExists()) {
        return '';
      }
      $dir = FBProductFeed::getFeedDirectory();
      return is_writable($dir) ? '' : $dir;
    } catch (Exception $e) {
      return '/media/';
    }
    return '';
  }

  private static function maybeSetPixelInstallTime() {
    // 1. new extension user: AKA pixel install time
    // 2. upgrading user: upgrade the version which support redirect FB CF
    $pixel_install_time =
      Mage::getStoreConfig('facebook_ads_toolbox/fbpixel/install_time');
    if (!$pixel_install_time) {
      Mage::getModel('core/config')->saveConfig(
        'facebook_ads_toolbox/fbpixel/install_time',
        Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
    }
  }
}
