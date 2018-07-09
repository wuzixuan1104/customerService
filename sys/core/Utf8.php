<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Utf8 {
  public static function init () {
    define ('UTF8_ENABLED',
      defined ('PREG_BAD_UTF8_ERROR')
        && (ICONV_ENABLED === true || MB_ENABLED === true)
        && 'UTF-8' === config ('other', 'charset')
    );
  }

  public static function cleanString ($str) {
    return self::isAscii ($str) === false
           ? !MB_ENABLED
             ? ICONV_ENABLED
               ? @iconv ('UTF-8', 'UTF-8//IGNORE', $str)
               : $str
             : mb_convert_encoding ($str, 'UTF-8', 'UTF-8')
           : $str;
  }

  public static function isAscii ($str) {
    return preg_match ('/[^\x00-\x7F]/S', $str) === 0;
  }

  // public static function safeAsciiForXml($str) {
  //   return remove_invisible_characters ($str, false);
  // }

  // public static function convert2utf8 ($str, $encoding) {
  //   return !MB_ENABLED
  //   ? ICONV_ENABLED
  //     ? @iconv($encoding, 'UTF-8', $str)
  //     : false 
  //   : mb_convert_encoding ($str, 'UTF-8', $encoding);
  // }
}
