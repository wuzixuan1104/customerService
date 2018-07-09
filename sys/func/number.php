<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('byte_format')) {
  function byte_format ($num, $precision = 1) {
    if ($num >= 1000000000000) {
      $num = round ($num / 1099511627776, $precision);
      $unit = 'TB';
    } else if ($num >= 1000000000) {
      $num = round ($num / 1073741824, $precision);
      $unit = 'GB';
    } else if ($num >= 1000000) {
      $num = round ($num / 1048576, $precision);
      $unit = 'MB';
    } else if ($num >= 1000) {
      $num = round ($num / 1024, $precision);
      $unit = 'KB';
    } else {
      $unit = 'Bytes';
      return number_format ($num) . ' ' . $unit;
    }

    return number_format ($num, $precision) . ' ' . $unit;
  }
}
