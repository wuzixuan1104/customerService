<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('valid_email')) {
  function valid_email ($email) {
    return (bool) filter_var ($email, FILTER_VALIDATE_EMAIL);
  }
}

if (!function_exists ('send_email')) {
  function send_email ($recipient, $subject, $message) {
    return mail ($recipient, $subject, $message);
  }
}
