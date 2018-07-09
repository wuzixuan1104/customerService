<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

date_default_timezone_set ('Asia/Taipei');

define ('SELF', pathinfo (__FILE__, PATHINFO_BASENAME));

$path = explode (DIRECTORY_SEPARATOR, dirname (str_replace (SELF, '', __FILE__)));
array_pop ($path);
array_pop ($path);

define ('EXT', '.php');
define ('FCPATH', implode (DIRECTORY_SEPARATOR, $path) . '/');
define ('APPPATH', FCPATH . 'app' . DIRECTORY_SEPARATOR);
define ('BASEPATH', FCPATH . 'sys' . DIRECTORY_SEPARATOR);
define ('ENVIRONMENT', 'cmd');
define ('CLI_LEN', 80);

if (!function_exists ('params')) {
  function params ($params, $keys) {
    $ks = $return = $result = array ();

    if (!($params && $keys))
      return $return;

    foreach ($keys as $key)
      if (is_array ($key)) foreach ($key as $k) array_push ($ks, $k);
      else  array_push ($ks, $key);

    $key = null;

    foreach ($params as $param)
      if (in_array ($param, $ks)) if (!isset ($result[$key = $param])) $result[$key] = array (); else ;
      else if (isset ($result[$key])) array_push ($result[$key], $param); else ;

    foreach ($keys as $key)
      if (is_array ($key))  foreach ($key as $k) if (isset ($result[$k])) $return[$key[0]] = isset ($return[$key[0]]) ? array_merge ($return[$key[0]], $result[$k]) : $result[$k]; else;
      else if (isset ($result[$key])) $return[$key] = isset ($return[$key]) ? array_merge ($return[$key], $result[$key]) : $result[$key]; else;

    return $return;
  }
}