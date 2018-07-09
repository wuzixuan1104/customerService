<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Load {
  private static $cache = array ();

  public static function file ($path, $must = false, $eval = null) {
    if (!empty (self::$cache[$path]))
      return true;

    if (!(is_file ($path) && is_readable ($path)))
      if ($must) $must === true && function_exists ('gg') ? gg ('載入檔案失敗。', 503, array ('detail' => array ('檔案路徑' => $path)), array (503, 'Service Unavailable')) : exit ('初始化失敗！');
      else return false;

    require_once $path;

    // is_callable ($eval) ? $eval () : 
    $eval === null || eval ($eval);

    return self::$cache = true;
  }

  public static function cmdCore ($file, $must = false, $eval = null) {
    return self::file (BASEPATH . 'cmd' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . $file, $must, $eval);
  }
  public static function cmdLib ($file, $must = false, $eval = null) {
    return self::file (BASEPATH . 'cmd' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $file, $must, $eval);
  }
  public static function cmdFunc ($file, $must = false, $eval = null) {
    return self::file (BASEPATH . 'cmd' . DIRECTORY_SEPARATOR . 'func' . DIRECTORY_SEPARATOR . $file, $must, $eval);
  }
  public static function sysCore ($file, $must = false, $eval = null) {
    return self::file (BASEPATH . 'core' . DIRECTORY_SEPARATOR . $file, $must, $eval);
  }
  public static function sysFunc ($helper, $must = false) {
    return self::file (BASEPATH . 'func' . DIRECTORY_SEPARATOR . $helper, $must, null);
  }
  public static function sysLib ($filename, $must = false, $eval = null) {
    return self::file (BASEPATH . 'lib' . DIRECTORY_SEPARATOR . $filename, $must, $eval);
  }
  public static function lib ($filename, $must = false, $eval = null) {
    return self::file (APPPATH . 'lib' . DIRECTORY_SEPARATOR . $filename, $must, $eval);
  }
  public static function func ($filename, $must = false, $eval = null) {
    return self::file (APPPATH . 'func' . DIRECTORY_SEPARATOR . $filename, $must, $eval);
  }
}