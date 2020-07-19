<?php

namespace Facebook\BusinessExtension\Helper;

use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\Util;

/**
 * Factory class for generating new ServerSideAPI events with default parameters.
 */
class ServerEventFactory {
  public static function newEvent( $eventName , $eventId = null){
    // Capture default user-data parameters passed down from the client browser.
    $userData = (new UserData())
                  ->setClientIpAddress(Util::getIpAddress())
                  ->setClientUserAgent(Util::getHttpUserAgent())
                  ->setFbp(Util::getFbp())
                  ->setFbc(Util::getFbc());

    $event = (new Event())
              ->setEventName($eventName)
              ->setEventTime(time())
              ->setEventSourceUrl(Util::getRequestUri())
              ->setUserData($userData)
              ->setCustomData(new CustomData());

    if( $eventId == null ){
      $event->setEventId(EventIdGenerator::guidv4());
    }
    else{
      $event->setEventId($eventId);
    }

    return $event;
  }

  // Fills customData member of $event with array $data
  private static function addCustomData( $event, $data ){
    $custom_data = $event->getCustomData();

    if (!empty($data['currency'])) {
      $custom_data->setCurrency($data['currency']);
    }

    if (!empty($data['value'])) {
      $custom_data->setValue($data['value']);
    }

    if (!empty($data['content_ids'])) {
      $custom_data->setContentIds($data['content_ids']);
    }

    if (!empty($data['content_type'])) {
      $custom_data->setContentType($data['content_type']);
    }

    if (!empty($data['content_name'])) {
      $custom_data->setContentName($data['content_name']);
    }

    if (!empty($data['content_category'])) {
      $custom_data->setContentCategory($data['content_category']);
    }

    if (!empty($data['search_string'])) {
      $custom_data->setSearchString($data['search_string']);
    }

    if (!empty($data['num_items'])) {
      $custom_data->setNumItems($data['num_items']);
    }

    if (!empty($data['contents'])) {
      $contents = array();
      foreach($data['contents'] as $content) {
        $contents[] = new Content($content);
      }
      $custom_data->setContents($contents);
    }

    if( !empty($data['order_id']) ){
      $custom_data->setOrderId($data['order_id']);
    }

    return $event;
  }

  //Creates a server side event
  public static function createEvent( $eventName, $data, $eventId = null ){
    $event = self::newEvent($eventName, $eventId);

    $event = self::addCustomData($event, $data);

    return $event;
  }
}
