<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('element')) {
  function element ($item, array $array, $default = null) {
    return array_key_exists ($item, $array) ? $array[$item] : $default;
  }
}

if (!function_exists ('random_element')) {
  function random_element ($array) {
    return is_array ($array) ? $array[array_rand ($array)] : $array;
  }
}

if (!function_exists ('elements')) {
  function elements ($items, array $array, $default = null) {
    $return = array ();

    is_array ($items) || $items = array ($items);

    foreach ($items as $item)
      $return[$item] = array_key_exists ($item, $array) ? $array[$item] : $default;

    return $return;
  }
}
