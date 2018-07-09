<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Charset {
  private static $funcOverload; 

  public static function init () {
    ini_set ('default_charset', $charset = config ('other', 'charset'));

    if (extension_loaded ('mbstring')) {
      define ('MB_ENABLED', true);
      @ini_set ('mbstring.internal_encoding', $charset);
      mb_substitute_character ('none');
    } else {
      define ('MB_ENABLED', false);
    }

    if (extension_loaded ('iconv')) {
      define ('ICONV_ENABLED', true);
      @ini_set ('iconv.internal_encoding', $charset);
    } else {
      define ('ICONV_ENABLED', false);
    }

    if (is_php ('5.6'))
      ini_set ('php.internal_encoding', $charset);

    foreach (array ('mbstring', 'hash', 'password', 'standard') as $name)
      Load::sysCore ('compat' . DIRECTORY_SEPARATOR . $name . EXT);

    isset (self::$funcOverload) || self::$funcOverload = (extension_loaded ('mbstring') && ini_get ('mbstring.func_overload'));
  }
  
  public static function funcOverload () {
    return self::$funcOverload;
  }
  
  public static function strlen ($str) {
    return self::$funcOverload ? mb_strlen ($str, '8bit') : strlen ($str);
  }

  public static function substr ($str, $start, $length = NULL) {
    if (self::$funcOverload) {
      isset ($length) || $length = ($start >= 0 ? self::strlen ($str) - $start : -$start);
      return mb_substr ($str, $start, $length, '8bit');
    }

    return isset ($length) ? substr ($str, $start, $length) : substr ($str, $start);
  }
}