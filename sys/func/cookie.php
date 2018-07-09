<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('set_cookie')) {
  function set_cookie ($name, $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = null, $httponly = null) {
    Input::setCookie ($name, $value, $expire, $domain, $path, $prefix, $secure, $httponly);
  }
}

if (!function_exists ('get_cookie')) {
  function get_cookie ($index, $xssClean = null) {
    is_bool ($xssClean) || $xssClean = (config ('other', 'global_xss_filtering') === true);
    $prefix = isset ($_COOKIE[$index]) ? '' : config ('cookie', 'prefix');
    Input::cookie ($prefix . $index, $xssClean);
  }
}

if (!function_exists ('delete_cookie')) {
  function delete_cookie ($name, $domain = '', $path = '/', $prefix = '') {
    set_cookie ($name, '', '', $domain, $path, $prefix);
  }
}
