<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (file_exists(__DIR__.'/../lib/fb.php')) {
  include_once __DIR__.'/../lib/fb.php';
} else {
  include_once 'Facebook_AdsExtension_lib_fb.php';
}

class FBProductFeed {

  const ATTR_ID = 'id';
  const ATTR_TITLE = 'title';
  const ATTR_DESCRIPTION = 'description';
  const ATTR_LINK = 'link';
  const ATTR_IMAGE_LINK = 'image_link';
  const ATTR_BRAND = 'brand';
  const ATTR_CONDITION = 'condition';
  const ATTR_AVAILABILITY = 'availability';
  const ATTR_PRICE = 'price';
  const ATTR_GOOGLE_PRODUCT_CATEGORY = 'google_product_category';
  const ATTR_SHORT_DESCRIPTION = 'short_description';
  const ATTR_PRODUCT_TYPE = 'product_type';
  const ATTR_GENDER = 'gender';

  const PATH_FACEBOOK_ADSEXTENSION_FEED_GENERATION_ENABLED =
    'facebook_adstoolbox/feed/generation/enabled';
  const PATH_FACEBOOK_ADSEXTENSION_FEED_GENERATION_FORMAT =
    'facebook_adstoolbox/feed/generation/format';
  const PRICE_PRECISION = 2;

  public static function log($info) {
    Mage::log($info, Zend_Log::INFO, FacebookAdsExtension::FEED_LOGFILE);
  }

  public static function logException($e) {
    Mage::log($e->getMessage(), Zend_Log::DEBUG, FacebookAdsExtension::FEED_EXCEPTION);
    Mage::log($e->getTraceAsString(), Zend_Log::DEBUG, FacebookAdsExtension::FEED_EXCEPTION);
    Mage::log($e, Zend_Log::DEBUG, FacebookAdsExtension::FEED_EXCEPTION);
  }

  public static function getCurrentSetup() {
    return array(
      'format' => Mage::getStoreConfig(
        self::PATH_FACEBOOK_ADSEXTENSION_FEED_GENERATION_FORMAT) ?: 'TSV',
      'enabled' => Mage::getStoreConfig(
        self::PATH_FACEBOOK_ADSEXTENSION_FEED_GENERATION_ENABLED) ?: false,
    );
  }

  protected function isValidCondition($condition) {
    return ($condition &&
              ( $condition === 'new' ||
                $condition === 'used' ||
                $condition === 'refurbished')
           );
  }

  protected function defaultBrand() {
    if (!isset($this->defaultBrand)) {
      $this->defaultBrand =
        $this->buildProductAttr(self::ATTR_BRAND, FacebookAdsExtension::getStoreName());
    }
    return $this->defaultBrand;
  }

  protected function defaultCondition() {
    return $this->buildProductAttr(self::ATTR_CONDITION, 'new');
  }

  protected function buildProductAttrText(
    $attr_name,
    $attr_value,
    $escapefn = null
  ) {
    // Facebook Product Feed attributes
    // ref: https://developers.facebook.com/docs/marketing-api/ \
    //   dynamic-product-ads/product-catalog
    switch ($attr_name) {
      case self::ATTR_ID:
      case self::ATTR_LINK:
      case self::ATTR_IMAGE_LINK:
      case self::ATTR_IMAGE_LINK:
      case self::ATTR_CONDITION:
      case self::ATTR_AVAILABILITY:
      case self::ATTR_PRICE:
      case self::ATTR_GENDER:
        if ((bool)$attr_value) {
          $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
          return trim($attr_value);
        }
        break;
      case self::ATTR_BRAND:
        if ((bool)$attr_value) {
          $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
          $attr_value = trim($attr_value);
          // brand max size: 70
          if (mb_strlen($attr_value) > 70) {
            $attr_value = mb_substr($attr_value, 0, 70);
          }
          return $attr_value;
        }
        break;
      case self::ATTR_TITLE:
        if ((bool)$attr_value) {
          $attr_value = $this->processAttrValue($attr_value, $escapefn);
          // title max size: 100
          if (mb_strlen($attr_value) > 100) {
            $attr_value = mb_substr($attr_value, 0, 100);
          }
          return $attr_value;
        }
        break;
      case self::ATTR_DESCRIPTION:
        if ((bool)$attr_value) {
          $attr_value = $this->processAttrValue($attr_value, $escapefn);
          // description max size: 5000
          if (mb_strlen($attr_value) > 5000) {
            $attr_value = mb_substr($attr_value, 0, 5000);
          }
          return $attr_value;
        }
        break;
      case self::ATTR_GOOGLE_PRODUCT_CATEGORY:
        // google_product_category max size: 250
        if ((bool)$attr_value) {
          if (mb_strlen($attr_value) > 250) {
            $attr_value = mb_substr($attr_value, 0, 250);
          }
          return $escapefn ? $this->$escapefn($attr_value) : $attr_value;
        }
        break;
      case self::ATTR_SHORT_DESCRIPTION:
        if ((bool)$attr_value) {
          $attr_value = $this->processAttrValue($attr_value, $escapefn);
          // max size: 1000
          // and replacing the last 3 characters with '...' if it's too long
          $attr_value = mb_strlen($attr_value) >= 1000 ?
            mb_substr($attr_value, 0, 995).'...' :
            $attr_value;
          return $attr_value;
        }
        break;
      case self::ATTR_PRODUCT_TYPE:
        // product_type max size: 750
        if ((bool)$attr_value) {
          $attr_value = $this->processAttrValue($attr_value, $escapefn);
          if (mb_strlen($attr_value) > 750) {
            $attr_value = mb_substr($attr_value, mb_strlen($attr_value) - 750, 750);
          }
          return $attr_value;
        }
        break;
    }
    return '';
  }

  protected function getFileName() {
    return '';
  }

  protected function buildHeader() {
    return '';
  }

  protected function buildFooter() {
    return '';
  }

  protected function buildProductAttr($attribute, $value) {
    return $this->buildProductAttrText($attribute, $value);
  }

  protected function isValidUrl($product_link) {
    return
      // This can fail for non unicode links.
      filter_var($product_link, FILTER_VALIDATE_URL) ||
      mb_substr($product_link, 0, 4) === 'http';
  }

  protected function buildProductEntry($product, $product_name) {
    $items = array();
    $stock = ($this->stock) ? $this->stock : Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    $title = $product_name ? $product_name : $product->getName();

    $items[self::ATTR_ID] = $this->buildProductAttr(self::ATTR_ID, $product->getId());
    $this->dedup_ids[$product->getId()] = true;

    $items[self::ATTR_TITLE] = $this->buildProductAttr(self::ATTR_TITLE, $title);

    // 'Description' is required by default but can be made
    // optional through the magento admin panel.
    // Try using the short description and title if it doesn't exist.
    $description = $this->buildProductAttr(
      self::ATTR_DESCRIPTION,
      $product->getDescription()
    );
    if (!$description) {
      $description = $this->buildProductAttr(
        self::ATTR_DESCRIPTION,
        $product->getShortDescription()
      );
    }

    $items[self::ATTR_DESCRIPTION] = ($description) ? $description : $items[self::ATTR_TITLE];
    // description can't be all uppercase
    $items[self::ATTR_DESCRIPTION] = $this->lowercaseIfAllCaps($items[self::ATTR_DESCRIPTION]);

    $product_link = $product->getProductUrl();
    if (!$this->isValidUrl($product_link)) {
      $product_link = $this->store_url . $product_link;
    }
    $items[self::ATTR_LINK] = $this->buildProductAttr(
      self::ATTR_LINK,
      $product_link);

    $items[self::ATTR_IMAGE_LINK] = $this->buildProductAttr(
      self::ATTR_IMAGE_LINK,
      $this->getImageURL($product));

    $brand = null;
    $brand = $this->getCorrectText($product, self::ATTR_BRAND, 'brand');
    if (!$brand) {
      $brand = $this->getCorrectText($product, self::ATTR_BRAND, 'manufacturer');
    }
    $items[self::ATTR_BRAND] = ($brand) ? $brand : $this->defaultBrand();

    $condition = null;
    if ($product->getData('condition')) {
      $condition = $this->buildProductAttr(self::ATTR_CONDITION, $product->getAttributeText('condition'));
    }
    $items[self::ATTR_CONDITION] = ($this->isValidCondition($condition)) ? $condition : $this->defaultCondition();

    $items[self::ATTR_AVAILABILITY] = $this->buildProductAttr(self::ATTR_AVAILABILITY,
      $stock->getIsInStock() ? 'in stock' : 'out of stock');

    $price = Mage::getModel('directory/currency')->formatPrecision(
      $this->getProductPrice($product),
      self::PRICE_PRECISION,
      array('display'=>Zend_Currency::NO_SYMBOL),
      false);
    if ($this->conversion_needed) {
      $price = $this->convertCurrency($price);
    }

    $items[self::ATTR_PRICE] = $this->buildProductAttr('price',
      sprintf('%s %s',
        $this->stripCurrencySymbol($price),
        Mage::app()->getStore($this->store_id)->getCurrentCurrencyCode()));

    $items[self::ATTR_SHORT_DESCRIPTION] = $this->buildProductAttr(self::ATTR_SHORT_DESCRIPTION,
      $product->getShortDescription());

    $items[self::ATTR_PRODUCT_TYPE] =
      $this->buildProductAttr(self::ATTR_PRODUCT_TYPE,
        $this->getCategoryPath($product));

    $items[self::ATTR_GOOGLE_PRODUCT_CATEGORY] =
      $this->buildProductAttr(self::ATTR_GOOGLE_PRODUCT_CATEGORY,
        $product->getData('google_product_category'));

    $gender = $this->getGenderInformation($product->getData('gender'));
    $items[self::ATTR_GENDER] = $this->buildProductAttr(self::ATTR_GENDER, $gender);

    return $items;
  }

  protected function htmlDecode($attr_value) {
    return strip_tags(html_entity_decode(($attr_value)));
  }

  private function getGenderInformation($gender_id) {
    if (!$gender_id) {
      return $gender_id;
    }

    if (!isset($this->gender_map)) {
      $this->gender_map = array();
    }

    if (isset($this->gender_map[$gender_id])) {
      return $this->gender_map[$gender_id];
    }

    $attribute_model = Mage::getModel('eav/entity_attribute');
    $attribute_code = $attribute_model->getIdByCode('catalog_product', 'gender');
    $attribute = $attribute_model->load($attribute_code);

    $attribute_table = Mage::getModel('eav/entity_attribute_source_table');
    $attribute_table->setAttribute($attribute);
    $gender = $attribute_table->getOptionText($gender_id);
    $gender = trim(strtolower($gender));

    if (!$gender) {
      return $gender;
    }

    if ($gender != 'male' && $gender != 'female') {
      $gender = 'unisex';
    }

    // Add this value to the cache
    $this->gender_map[$gender_id] = $gender;
    return $gender;
  }

  // Generates a map of the form : 4 => "Root > Mens > Shoes"
  private function generateCategoryNameMap() {
    $categories = Mage::getModel('catalog/category')->getCollection()
      ->addAttributeToSelect('name')
      ->addAttributeToSelect('path')
      ->addAttributeToSelect('is_active')
      ->addAttributeToFilter('is_active', 1);
    $name = array();
    $breadcrumb = array();
    foreach ($categories as $category)
    {
      $entity_id = $category->getId();
      $name[$entity_id] = $category->getName();
      $breadcrumb[$entity_id] = $category->getPath();
    }
    // Converts the product category paths to human readable form.
    // e.g.  "1/2/3" => "Root > Mens > Shoes"
    foreach ($name as $id => $value)
    {
        $breadcrumb[$id] = implode(" > ", array_filter(array_map(
            function ($inner_id) use (&$name) {
              return isset($name[$inner_id]) ? $name[$inner_id] : null;
            },
            explode("/", $breadcrumb[$id])
        )));
    }
    return $breadcrumb;
  }

  public function save() {
    $io = new Varien_Io_File();
    $feed_file_path =
      Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/';

    $io->open(array('path' => $feed_file_path));
    if ($io->fileExists($feed_file_path) &&
        !$io->isWriteable($feed_file_path)) {
      self::log('Feed file is not writable');
      Mage::throwException(Mage::helper('Facebook_AdsExtension')->__(
        'File "%s" cannot be saved. Please make sure the path "%s" is '.
        'writable by web server.',
        $feed_file_path));
    }

    self::log('Generating Categories');
    $this->categoryNameMap = $this->generateCategoryNameMap();

    $io->streamOpen($this->getFileName());
    self::log('going to generate file:'.$this->getFileName());

    $io->streamWrite($this->buildHeader()."\n");

    $this->store_id = FacebookAdsExtension::getDefaultStoreId();

    $collection = Mage::getModel('catalog/product')->getCollection()
      ->addStoreFilter($this->store_id);
    $total_number_of_products = $collection->getSize();
    unset($collection);

    $this->writeProducts($io, $total_number_of_products, true);

    $footer = $this->buildFooter();
    if ($footer) {
      $io->streamWrite($footer."\n");
    }
  }

  private function writeProducts($io, $total_number_of_products, $should_log) {
    $count = 0;
    $batch_max = 100;

    $locale_code = Mage::app()->getLocale()->getLocaleCode();
    $symbols = Zend_Locale_Data::getList($locale_code, 'symbols');
    $this->group_separator = $symbols['group'];
    $this->decimal_separator = $symbols['decimal'];
    $this->conversion_needed = $this->isCurrencyConversionNeeded();
    $skip_count = 0;
    $exception_count = 0;
    Mage::app()->setCurrentStore($this->store_id);

    $this->store_url = Mage::app()
      ->getStore()
      ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

    if ($should_log) {
      self::log(sprintf('About to begin writing %d products',$total_number_of_products));
      self::log(sprintf('Store id = %d ', $this->store_id));
    }
    $this->dedup_ids = array();

    $time_limit = (int) ini_get('max_execution_time');
    if ($time_limit !== 0 && $time_limit < 1800) {
      set_time_limit(1800);
    }
    while ($count < $total_number_of_products) {
      // Compute and log memory usage
      self::log(
        sprintf(
          "Current Memory usage: %f M / %s",
          memory_get_usage() / (1024.0 * 1024.0), // Value returned is in bytes
          ini_get('memory_limit')));

      if ($should_log) {
       self::log(
        sprintf(
          "scanning products [%d -> %d)...\n",
          $count,
          ($count + $batch_max) >= $total_number_of_products ?
            $total_number_of_products :
            ($count + $batch_max)));
      }

      $products = Mage::getModel('catalog/product')->getCollection()
        ->addAttributeToSelect('*')
        ->addStoreFilter()
        ->setPageSize($batch_max)
        ->setCurPage($count / $batch_max + 1)
        ->addUrlRewrite();

      foreach ($products as $product) {
        try {
          $product_name = $product->getName();
          $product_id = $product->getId();
          if ($product->getVisibility() != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE &&
              $product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_DISABLED &&
              $product_name &&
              $product_id &&
              !isset($this->dedup_ids[$product_id])) {
            $e = $this->buildProductEntry($product, $product_name);
            $io->streamWrite($e."\n");
          } else {
            $skip_count++;
          }
        } catch (\Exception $e) {
          $exception_count++;
          // Don't overload the logs, log the first 3 exceptions.
          if ($exception_count <= 3) {
            self::logException($e);
          }
          // If it looks like a systemic failure : stop feed generation.
          if ($exception_count > 100) {
            throw $e;
          }
        }
        $product->clearInstance();
      }
      unset($products);
      $count += $batch_max;
    }

    if ($skip_count != 0) {
      self::log(sprintf('skipped %d products', $skip_count));
    }

    if ($exception_count != 0) {
      self::log("Exceptions in Feed Generation : ".$exception_count);
    }
  }

  public static function getFeedDirectory() {
    return Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/';
  }

  public function estimateGenerationTime() {
    $timestamp =
      Mage::getStoreConfig('facebook_ads_toolbox/dia/feed/last_estimated');
    if ($timestamp && !self::isStale($timestamp)) {
      return
        Mage::getStoreConfig('facebook_ads_toolbox/dia/feed/time_estimate');
    }

    $io = new Varien_Io_File();
    $io->open(array('path' => self::getFeedDirectory()));
    $io->streamOpen('feed_dryrun.txt');

    $this->store_id = FacebookAdsExtension::getDefaultStoreId();

    $collection = Mage::getModel('catalog/product')->getCollection();
    $total_number_of_products = $collection->getSize();
    unset($collection);
    $this->categoryNameMap = array();

    $num_samples =
      ($total_number_of_products <= 500) ? $total_number_of_products : 500;

    $start_time = microtime(true);
    $this->writeProducts($io, $num_samples, false);
    $end_time = microtime(true); // Returns a float in seconds.

    if ($num_samples == 0) {
      return 30;
    }
    $time_spent = ($end_time - $start_time);

    // Estimated Time =
    // 150% of Linear extrapolation of the time to generate 500 products
    // + 30 seconds of buffer time.
    $time_estimate =
      $time_spent * $total_number_of_products / $num_samples * 1.5 + 30;

    self::log('Feed Generation Time Estimate: '.$time_estimate);

    Mage::getModel('core/config')->saveConfig(
      'facebook_ads_toolbox/dia/feed/time_estimate',
      $time_estimate
    );
    Mage::getModel('core/config')->saveConfig(
      'facebook_ads_toolbox/dia/feed/last_estimated',
      time()
    );
    return $time_estimate;
  }

  public function read() {
    $feed_file_path = $this->getFullPath();
    return array(
      basename($feed_file_path),
      filesize($feed_file_path),
      file_get_contents($feed_file_path),
    );
  }

  public function saveGZip() {
    self::log(sprintf("generating gzip copy of %s ...", $this->getFileName()));
    $feed_file_path = $this->getFullPath();
    $gz_file_path = $feed_file_path.'.gz';
    $fp = gzopen($gz_file_path, 'w9');
    gzwrite($fp, file_get_contents($feed_file_path));
    gzclose($fp);
    self::log("generated!");
  }

  public function readGZip() {
    $feed_file_path = $this->getFullPath();
    $gz_file_path = $feed_file_path.'.gz';
    return array(
      basename($gz_file_path),
      filesize($gz_file_path),
      file_get_contents($gz_file_path),
    );
  }

  public function getFullPath() {
    return Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).'/'.$this->getFileName();
  }

  private static function isStale($time_file_modified) {
    return (!$time_file_modified || time() - $time_file_modified > 8*3600);
  }

  public function getTargetFilePath($supportzip) {
    $feed_file_path = $this->getFullPath();
    return $supportzip ? $feed_file_path.'.gz' : $feed_file_path;
  }

  public static function fileIsStale($file_path) {
    $time_file_modified = (file_exists($file_path)) ? filemtime($file_path) : 0;

    // if we get no file modified time, or the modified time is 8hours ago,
    // we count it as stale
    if (!$time_file_modified) {
      return true;
    } else {
      return self::isStale($time_file_modified);
    }
  }

  public function cacheIsStale($supportzip) {
    $file_path = $this->getTargetFilePath($supportzip);
    $time_now = time();
    return self::fileIsStale($file_path);
  }

  private function processAttrValue($attr_value, $escapefn) {
    $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
    $attr_value = $this->htmlDecode($attr_value);
    $attr_value = $escapefn ? $this->$escapefn($attr_value) : $attr_value;
    return trim($attr_value);
  }

  private function lowercaseIfAllCaps($string) {
    // if contains lowercase, don't update string
    if (!preg_match('/[a-z]/', $string)) {
      if (mb_strtoupper($string, 'utf-8') === $string) {
        return mb_strtolower($string, 'utf-8');
      }
    }
    return $string;
  }

  private function getCorrectText($product, $column, $attribute) {
    if ($product->getData($attribute)) {
      $text = $this->buildProductAttr($column, $product->getAttributeText($attribute));
      if (!$text) {
        $text = $this->buildProductAttr($column, $product->getData($attribute));
      }
      return $text;
    }
    return null;
  }

  public function isCurrencyConversionNeeded() {
    if ($this->group_separator !== ',' && $this->group_separator !== '.') {
      return true;
    } else if ($this->decimal_separator !== ',' &&
      $this->decimal_separator !== '.') {
      return true;
    } else {
      return false;
    }
  }

  private function convertCurrency($price) {
    $price = str_replace($this->group_separator, '', $price);
    $price = str_replace($this->decimal_separator, '.', $price);
    return $price;
  }

  private function getImageURL($product) {
    $image_url = null;
    $image = $product->getImage();
    if (!$image || $image === '' || $image === 'no_selection') {
      $product->load('media_gallery');
      $gal = $product->getMediaGalleryImages();
      if ($gal) {
        foreach ($gal as $gal_image) {
          if ($gal_image['url'] && $gal_image['url'] !== '') {
            $image_url = $gal_image['url'];
            break;
          }
        }
      }
    }
    if (!$image_url) {
      $image_url = FacebookAdsExtension::getBaseUrlMedia().'catalog/product'.$image;
    }
    return $image_url;
  }

  private function getProductPrice($product) {
    $price = 0.0;
    switch ($product->getTypeId()) {
    case 'configurable':
      $price = $this->getConfigurableProductPrice($product);
      break;
    case 'grouped':
      $price = $this->getGroupedProductPrice($product);
      break;
    case 'bundle':
      $price =  $this->getBundleProductPrice($product);
      break;
    default:
      $price = $this->getFinalPrice($product);
    }

    if (!isset($this->base_currency)) {
      $this->base_currency = Mage::app()->getStore($this->store_id)->getBaseCurrencyCode();
    }
    if (!isset($this->current_currency)) {
      $this->current_currency = Mage::app()->getStore($this->store_id)->getCurrentCurrencyCode();
    }
    if ($this->base_currency === $this->current_currency) {
      return $price;
    }

    if (!isset($this->currency_rate)) {
      $this->currency_rate =
        Mage::getModel('directory/currency')->getCurrencyRates(
          $this->base_currency, array($this->current_currency)
        );
      $this->currency_rate =
        is_array($this->currency_rate) ? end($this->currency_rate) : 0;
    }

    if (!$this->currency_rate || is_nan($this->currency_rate)) {
      self::log("ERROR : Currency Conversion Rate Is 0/Infinity.");
      throw new Exception(
        "ERROR : Currency Conversion Rate Is 0/Infinity.\n".
        "Failed when converting ".$this->base_currency." to ".$this->current_currency.
        " getCurrencyRate() returned ".($this->currency_rate ?: " NULL")."\n".
        " This can be fixed by setting your currency rates in ".
        "System > Currency > Rates"
      );
    } else {
      return Mage::helper('directory')->currencyConvert(
        $price,
        $this->base_currency,
        $this->current_currency
      );
    }
  }

  private function getConfigurableProductPrice($product) {
    if ($product->getFinalPrice() === 0) {
      $configurable = Mage::getModel('catalog/product_type_configurable')
        ->setProduct($product);
      $simple_collection = $configurable->getUsedProductCollection()
        ->addAttributeToSelect('price')->addFilterByRequiredOptions();
      foreach ($simple_collection as $simple_product) {
        if ($simple_product->getPrice() > 0) {
          return $this->getFinalPrice($simple_product);
        }
      }
    }
    return $this->getFinalPrice($product);
  }

  private function getBundleProductPrice($product) {
    return $product->getPriceModel()->getTotalPrices($product, 'min', 1, 1);
  }

  private function getGroupedProductPrice($product) {
    $assoc_products = $product->getTypeInstance(true)
      ->getAssociatedProductCollection($product)
      ->addAttributeToSelect('price')
      ->addAttributeToSelect('tax_class_id')
      ->addAttributeToSelect('tax_percent');

    $min_price = INF;
    foreach ($assoc_products as $assoc_product) {
      $min_price = min($min_price, $this->getFinalPrice($assoc_product));
      }
    return $min_price;
  }

  private function getFinalPrice($product, $price = null) {
    if (!isset($this->taxHelper)) {
      $this->taxHelper = Mage::helper('tax');
    }
    if ($price === null) {
      $price = $product->getFinalPrice();
    }
    if ($price === null) {
      $price = $product->getData('special_price');
    }
    return $this->taxHelper->getPrice($product, $price);
  }

  private function stripCurrencySymbol($price) {
    if (!isset($this->currency_strip_needed)) {
      $this->currency_strip_needed = !preg_match('/^[0-9,.]*$/', $price);
    }
    if ($this->currency_strip_needed) {
      return preg_replace('/[^0-9,.]/', '', $price);
    } else {
      return $price;
    }
  }

  private function getCategoryPath($product) {
    $category_names = array();
    $category_ids = $product->getCategoryIds();
    foreach ($category_ids as $category_id) {
      if (array_key_exists($category_id, $this->categoryNameMap)) {
        $category_names[] = $this->categoryNameMap[$category_id];
      }
    }
    return implode(" | ", $category_names);
  }
}
