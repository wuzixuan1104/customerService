<?php
require FCPATH . 'vendor/autoload.php';
use Facebook\FacebookApp;
use Facebook\SignedRequest;

defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Facebook {
  private static $fb = null;
  private static $accessToken = null;

  public static function fb () {
    if (self::$fb !== null)
      return self::$fb;

    return self::$fb = new Facebook\Facebook (array (
        'app_id' => config ('facebook', 'appId'),
        'app_secret' => config ('facebook', 'secret'),
        'default_graph_version' => config ('facebook', 'version')
      ));
  }

  public static function loginUrl () {
    if (session_status () == PHP_SESSION_NONE)
      session_start ();

    $helper = self::fb ()->getRedirectLoginHelper ();
    $permissions = config ('facebook', 'scope');
    return $helper->getLoginUrl (URL::base (func_get_args ()), $permissions);
  }
  public static function logoutUrl () {
    return URL::base (func_get_args ());
  }
  public static function login () {
    if (session_status() == PHP_SESSION_NONE)
      session_start();

    $helper = self::fb ()->getRedirectLoginHelper ();

    try {
      self::$accessToken = $helper->getAccessToken ();
      return true;
    } catch(Exception $e) {
      return false;
    }
    return false;
  }

  public static function me () {
    if (!(self::fb () && self::$accessToken))
      return null;
    $get_fields = implode (',', config ('facebook', 'get_fields'));
    self::fb ()->setDefaultAccessToken (self::$accessToken);
    return self::fb ()->get ('/me' . ($get_fields ? '?fields=' . $get_fields : ''))->getGraphUser ();
  }
}