<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('set_realpath')) {
  function set_realpath ($path, $check_existance = false) {
    if (preg_match ('#^(http:\/\/|https:\/\/|www\.|ftp|php:\/\/)#i', $path) || filter_var ($path, FILTER_VALIDATE_IP) === $path)
      gg ('路徑必須是本地服務器路徑，而不是網址。');

    if (realpath ($path) !== false)
      $path = realpath ($path);
    elseif ($check_existance && !is_dir ($path) && !is_file ($path))
      gg ('無效的路徑：'.$path);

    return is_dir ($path) ? rtrim ($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $path;
  }
}
