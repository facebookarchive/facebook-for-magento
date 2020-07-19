<?php

namespace Facebook\BusinessExtension\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;

/**
 * Helper class to get data using Magento Platform methods.
 */
class MagentoDataHelper extends AbstractHelper {

  /**
   * @var \Magento\Framework\ObjectManagerInterface
   */
  protected $_objectManager;

  /**
   * @var \Facebook\BusinessExtension\Logger\Logger
   */
  protected $_logger;

  /**
   * @var \Magento\Catalog\Model\ProductFactory
   */
  protected $_productFactory;

  /**
   * @var \Magento\Store\Model\StoreManagerInterface
   */
  protected $_storeManager;

  public function __construct(
    Context $context,
    ObjectManagerInterface $objectManager,
    \Facebook\BusinessExtension\Logger\Logger $logger,
    \Magento\Catalog\Model\ProductFactory $productFactory,
    \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
      parent::__construct($context);
      $this->_objectManager = $objectManager;
      $this->_logger = $logger;
      $this->_productFactory = $productFactory;
      $this->_storeManager = $storeManager;
  }

  /**
   * Returns currently logged in users's email.
   *
   * @return string
   */
  public function getEmail(){

    $currentSession = $this->_objectManager->get('Magento\Customer\Model\Session');
    return $currentSession->getCustomer()->getEmail();
  }

  /**
   * Returns currently logged in users' First Name.
   *
   * @return string
   */
  public function getFirstName(){

    $currentSession = $this->_objectManager->get('Magento\Customer\Model\Session');
    return $currentSession->getCustomer()->getFirstname();
  }

  /**
   * Returns currently logged in users' Last Name.
   *
   * @return string
   */
  public function getLastName(){

    $currentSession = $this->_objectManager->get('Magento\Customer\Model\Session');
    return $currentSession->getCustomer()->getLastname();
  }

  /**
   * Returns currently logged in users' Date of Birth.
   *
   * @return string
   */
  public function getDateOfBirth(){

    $currentSession = $this->_objectManager->get('Magento\Customer\Model\Session');
    return $currentSession->getCustomer()->getDob();
  }

  /**
   * Returns the product with the given sku
   * @param string $productSku
   * @return \Magento\Catalog\Model\Product
   */
   public function getProductWithSku($productSku){
      $product = $this->_productFactory->create();
      $product->load($product->getIdBySku($productSku));
      return $product;
   }

   /**
   * Returns the categories for the given product
   * @param \Magento\Catalog\Model\Product $product
   * @return string
   */
   public function getCategoriesForProduct($product){
      $categoryIds = $product->getCategoryIds();
      if (count($categoryIds) > 0) {
        $categoryNames = array();
        $categoryModel = $this->_objectManager->get('Magento\Catalog\Model\Category');
        foreach ($categoryIds as $categoryId) {
          $category = $categoryModel->load($categoryId);
          $categoryNames[] = $category->getName();
        }
        return addslashes(implode(',', $categoryNames));
      } else {
        return null;
      }
   }

   /**
   * Returns the price for the given product
   * @param \Magento\Catalog\Model\Product $product
   * @return int
   */
  public function getValueForProduct($product) {
    $price = $product->getFinalPrice();
    $priceHelper = $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data');
    return $priceHelper->currency($price, false, false);
  }

  /**
   * Returns the currency used in the store
   * @return string
  */
  public function getCurrency() {
    return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
  }

  /**
   * Returns the ids of the items added to the cart
   * @return string[]
  */
  public function getCartContentIds() {
    $productIds = array();
    $cart = $this->_objectManager->get('\Magento\Checkout\Model\Cart');
    if(!$cart || !$cart->getQuote()) {
      return null;
    }
    $items = $cart->getQuote()->getAllVisibleItems();
    $productModel = $this->_objectManager->get('\Magento\Catalog\Model\Product');
    foreach ($items as $item) {
      $product = $productModel->load($item->getProductId());
      $productIds[] = $product->getId();
    }
    return $productIds;
  }

  /**
   * Returns the cart total value
   * @return int
  */
  public function getCartTotal() {
    $cart = $this->_objectManager->get('\Magento\Checkout\Model\Cart');
    if(!$cart || !$cart->getQuote()) {
      return null;
    }
    $subtotal = $cart->getQuote()->getSubtotal();
    if ($subtotal) {
      $priceHelper = $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data');
      return $priceHelper->currency($subtotal, false, false);
    } else {
      return null;
    }
  }

  /**
   * Returns the amount of items in the cart
   * @return int
  */
  public function getCartNumItems() {
    $cart = $this->_objectManager->get('\Magento\Checkout\Model\Cart');
    if(!$cart || !$cart->getQuote()) {
      return null;
    }
    $numItems = 0;
    $items = $cart->getQuote()->getAllVisibleItems();
    foreach ($items as $item) {
      $numItems += $item->getQty();
    }
    return $numItems;
  }

  /**
   * Returns information about the cart items
   * @return array
  */
  public function getCartContents() {
    $cart = $this->_objectManager->get('\Magento\Checkout\Model\Cart');
    if(!$cart || !$cart->getQuote()) {
      return null;
    }
    $contents = array();
    $items = $cart->getQuote()->getAllVisibleItems();
    $productModel = $this->_objectManager->get('\Magento\Catalog\Model\Product');
    $priceHelper = $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data');
    foreach ($items as $item) {
      $product = $productModel->load($item->getProductId());
      $contents[] = array(
        'product_id' => $product->getId(),
        'quantity' => $item->getQty(),
        'item_price' => $priceHelper->currency($product->getFinalPrice(), false, false)
      );
    }
    return $contents;
  }

  /**
   * Returns the ids of the items in the last order
   * @return string[]
  */
  public function getOrderContentIds() {
    $order = $this->_objectManager->get('\Magento\Checkout\Model\Session')->getLastRealOrder();
    if (!$order) {
      return null;
    }
    $productIds = array();
    $items = $order->getAllVisibleItems();
    $productModel = $this->_objectManager->get('\Magento\Catalog\Model\Product');
    foreach ($items as $item) {
      $product = $productModel->load($item->getProductId());
      $productIds[] = $product->getId();
    }
    return $productIds;
  }

  /**
   * Returns the last order total value
   * @return string
  */
  public function getOrderTotal() {
    $order = $this->_objectManager->get('\Magento\Checkout\Model\Session')->getLastRealOrder();
    if (!$order) {
      return null;
    }
    $subtotal = $order->getSubTotal();
    if ($subtotal) {
      $priceHelper = $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data');
      return $priceHelper->currency($subtotal, false, false);
    } else {
      return null;
    }
  }

  /**
   * Returns information about the last order items
   * @return array
  */
  public function getOrderContents() {
    $order = $this->_objectManager->get('\Magento\Checkout\Model\Session')->getLastRealOrder();
    if (!$order) {
      return null;
    }
    $contents = array();
    $items = $order->getAllVisibleItems();
    $productModel = $this->_objectManager->get('\Magento\Catalog\Model\Product');
    $priceHelper = $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data');
    foreach ($items as $item) {
      $product = $productModel->load($item->getProductId());
      $contents[] = array(
        'product_id' => $product->getId(),
        'quantity' => intval($item->getQtyOrdered()),
        'item_price' => $priceHelper->currency($product->getFinalPrice(), false, false)
      );
    }
    return $contents;
  }

  /**
   * Returns the id of the last order
   * @return int
  */
  public function getOrderId() {
    $order = $this->_objectManager->get('\Magento\Checkout\Model\Session')->getLastRealOrder();
    if (!$order) {
      return null;
    }
    else{
      return $order->getId();
    }
  }

  /**
   * Returns an object representing the current logged in customer
   * @return Magento\Customer\Model\Customer
   */
  public function getCurrentCustomer(){
    $session = $this->_objectManager->create('Magento\Customer\Model\Session');
    if(!$session->isLoggedIn()){
      return null;
    }
    else{
      return $session->getCustomer();
    }
  }

  /**
   * Returns the address of a given customer
   * @return Magento\Customer\Model\Address
   */
  public function getCustomerAddress($customer){
    $customerAddressId = $customer->getDefaultBilling();
    $address = $this->_objectManager->get('Magento\Customer\Model\Address');
    $address->load($customerAddressId);
    return $address;
  }

  /**
   * Returns the region's code for the given address
   * @return array
  */
  public function getRegionCodeForAddress($address){
    $region = $this ->_objectManager->get('\Magento\Directory\Model\Region')
                    ->load($address->getRegionId());
    if($region){
      return $region->getCode();
    }
    else{
      return null;
    }
  }

  /**
   * Returns the string representation of the customer gender
   * @return string
  */
  public function getGenderAsString($customer){
    if( $customer->getGender() ){
      return $customer->getResource()->getAttribute('gender')->getSource()->getOptionText($customer->getGender());
    }
    return null;
  }
  // TODO Remaining user/custom data methods that can be obtained using Magento.
}
