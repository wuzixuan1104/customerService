<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('asset')) {
  function asset () {
    $args = func_get_args ();
    $args = ltrim (preg_replace ('/\/+/', '/', implode ('/', array_2d_to_1d ($args))), '/');
    
    return URL::base ($args);
  }
}

if (!function_exists ('remove_ckedit_tag')) {
  function remove_ckedit_tag ($text) {
    return preg_replace ("/\s+/", "", preg_replace ("/&#?[a-z0-9]+;/i", "", str_replace ('▼', '', str_replace ('▲', '', trim (strip_tags ($text))))));
  }
}