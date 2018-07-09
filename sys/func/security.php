<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('sanitizeFilename')) {
  function sanitizeFilename ($filename) {
    return Security::sanitizeFilename ($filename);
  }
}

if (!function_exists ('stripImageTags')) {
  function stripImageTags ($str) {
    return Security::stripImageTags($str);
  }
}

if (!function_exists ('xss_clean')) {
  function xss_clean ($str, $is_image = false) {
    return Security::xssClean ($str, $is_image);
  }
}

if (!function_exists ('do_hash')) {
  function do_hash ($str, $type = 'sha1') {
    if (!in_array (strtolower ($type), hash_algos ()))
      $type = 'md5';

    return hash ($type, $str);
  }
}

if (!function_exists ('encode_php_tags')) {
  function encode_php_tags ($str) {
    return str_replace (array ('<?', '?>'), array ('&lt;?', '?&gt;'), $str);
  }
}
