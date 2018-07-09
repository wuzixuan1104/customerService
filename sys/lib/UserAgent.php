<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class UserAgent {
  private static $agent = null;
  private static $platform = null;
  private static $robot = null;
  private static $browser = null;
  private static $mobile = null;
  private static $version = null;

  private static $languages = array ();
  private static $charsets = array ();
  private static $referrer = null;
  
  public static function init () {
    if (!isset ($_SERVER['HTTP_USER_AGENT']))
      return;
    
    self::$agent = trim ($_SERVER['HTTP_USER_AGENT']);

    if (!$agents = config ('user_agents'))
      return ;

    $platforms = isset ($agents['platforms']) ? $agents['platforms'] : array ();
    $browsers = isset ($agents['browsers']) ? $agents['browsers'] : array ();
    $mobiles = isset ($agents['mobiles']) ? $agents['mobiles'] : array ();
    $robots = isset ($agents['robots']) ? $agents['robots'] : array ();

    self::setPlatform ($platforms);
    self::setBrowser ($browsers);
    self::setMobile ($mobiles);
    self::setRobot ($robots);
    
    self::setLanguages ();
    self::setCharsets ();
    self::setReferrer ();
  }

  // -----------
  
  private static function setPlatform (array $platforms = array ()) {
    foreach ($platforms as $key => $val)
      if (preg_match ('|' . preg_quote ($key) . '|i', self::$agent))
        return (self::$platform = $val) || true;

    return (self::$platform = 'Unknown Platform') && false;
  }

  private static function setBrowser (array $browsers = array ()) {
    foreach ($browsers as $key => $val)
      if (preg_match ('|' . $key . '.*?([0-9\.]+)|i', self::$agent, $match)) {
        self::$version = $match[1];
        self::$browser = $val;
        return true;
      }

    return false;
  }

  private static function setMobile (array $mobiles = array ()) {
    foreach ($mobiles as $key => $val)
      if (false !== (stripos (self::$agent, $key)))
        return (self::$mobile = $val) ||  true;

    return false;
  }

  private static function setRobot (array $robots = array ()) {
    foreach ($robots as $key => $val)
      if (preg_match ('|' . preg_quote ($key) . '|i', self::$agent))
        return (self::$robot = $val) || true;

    return false;
  }

  private static function setLanguages () {
    if (isset ($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'])
      self::$languages = explode (',', preg_replace ('/(;\s?q=[0-9\.]+)|\s/i', '', strtolower (trim ($_SERVER['HTTP_ACCEPT_LANGUAGE']))));

    self::$languages || self::$languages = array ('Undefined');

    return true;
  }

  private static function setCharsets () {
    if (isset ($_SERVER['HTTP_ACCEPT_CHARSET']) && $_SERVER['HTTP_ACCEPT_CHARSET'])
      self::$charsets = explode (',', preg_replace ('/(;\s?q=.+)|\s/i', '', strtolower (trim ($_SERVER['HTTP_ACCEPT_CHARSET']))));
    
    self::$charsets || self::$charsets = array ('Undefined');

    return true;
  }

  private static function setReferrer () {
    self::$referrer !== null || self::$referrer = isset ($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] ? trim ($_SERVER['HTTP_REFERER']) : '';
    return self::$referrer;
  }

  // -----------

  public static function getAgent () {
    return self::$agent;
  }
  public static function getPlatform () {
    return self::$platform;
  }

  public static function getBrowser () {
    return self::$browser;
  }

  public static function getMobile () {
    return self::$mobile;
  }

  public static function getRobot () {
    return self::$robot;
  }

  public static function getLanguages () {
    return self::$languages;
  }

  public static function getCharsets () {
    return self::$charsets;
  }

  public static function getReferrer () {
    return self::$referrer;
  }

  // -----------

  public static function isBrowser () {
    return self::getBrowser () !== null;
  }

  public static function isMobile () {
    return self::getMobile () !== null;
  }

  public static function isRobot () {
    return self::getRobot () !== null;
  }

  public static function isReferral () {
    if (self::$referer === null)
      return false;

    $referer_host = @parse_url ($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $own_host = parse_url (config ('other', 'base_url'), PHP_URL_HOST);

    return ($referer_host && $referer_host !== $own_host);
  }
}
