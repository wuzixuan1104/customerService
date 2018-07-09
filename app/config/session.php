<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
  'driver' => 'file', //file 、 database 、 redis 、 memcached
  'cookie_name' => 'oaci_session',
  'expiration' => 7200, // 存活週期
  'time_to_update' => 300, // 更新 session ID 週期
  'regenerate_destroy' => false,
  'match_ip' => false,

  'drivers' => array (
      'file' => array (
          'path' => FCPATH . 'session' . DIRECTORY_SEPARATOR,
        ),
      'database' => array (
          'model' => 'SessionData',
        ),
      'redis' => array (
          'prefix' => 'oaci_session:',
          'host' => 'localhost',
          'port' => '6379',
          'password' => null,
          'database' => null,
          'timeout' => null,
        ),
      'memcached' => array (
          'prefix' => 'oaci_session:',
          'servers' => array (
              array (
                  'host' => 'localhost',
                  'port' => '11211',
                  'weight' => 0,
                ),
            ),
        ),
    ),
);
