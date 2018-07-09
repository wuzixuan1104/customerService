<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
  'auto_load' => true,
  'cache' => array (
      'enable' => ENVIRONMENT == 'production',
      'driver' => 'file', //  | file   | redis   | memcached
      'prefix' => 'query-', // | query- | query:  | query:
      'expire' => 30 //sec
    ),
);