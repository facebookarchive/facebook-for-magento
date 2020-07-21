<?php

namespace Facebook\BusinessExtension\Helper;

/**
  * Class that contains the keys used to identify each field in AAMSettings
  * "em","fn","ln","ge","ph","ct","st","zp" are currently returned by the endpoint
*/
abstract class AAMSettingsFields{
  const EMAIL = "em";
  const FIRST_NAME = "fn";
  const LAST_NAME = "ln";
  const GENDER = "ge";
  const PHONE = "ph";
  const CITY = "ct";
  const STATE = "st";
  const ZIP_CODE = "zp";
  const DATE_OF_BIRTH = "db";
  const COUNTRY = "cn";
  public static function getAllFields(){
    return array(
      self::EMAIL,
      self::FIRST_NAME,
      self::LAST_NAME,
      self::GENDER,
      self::PHONE,
      self::CITY,
      self::STATE,
      self::ZIP_CODE,
      self::DATE_OF_BIRTH,
      self::COUNTRY
    );
  }
}
