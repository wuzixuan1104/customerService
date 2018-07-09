<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (MB_ENABLED === true)
  return;

if (!function_exists ('mb_strlen')) {
  function mb_strlen ($str, $encoding = null) {
    if (ICONV_ENABLED !== true)
      return strlen ($str);

    return iconv_strlen ($str, $encoding ? $encoding : config ('other', 'charset'));
  }
}

if (!function_exists ('mb_strpos')) {
  function mb_strpos ($haystack, $needle, $offset = 0, $encoding = null) {
    if (ICONV_ENABLED !== true)
      return strpos ($haystack, $needle, $offset);
  
    return iconv_strpos ($haystack, $needle, $offset, $encoding ? $encoding : config ('other', 'charset'));
  }
}

if (!function_exists ('mb_substr')) {
  function mb_substr ($str, $start, $length = null, $encoding = null) {
    if (ICONV_ENABLED !== true)
      return isset ($length) ? substr ($str, $start, $length) : substr ($str, $start);
      
    $encoding || $encoding = config ('other', 'charset');
    $length || $length = iconv_strlen ($str, $encoding);
    return iconv_substr ($str, $start, $length, $encoding);
  }
}
