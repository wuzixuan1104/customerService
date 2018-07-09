<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
  'output' => array (
      'path' => FCPATH . 'cache' . DIRECTORY_SEPARATOR,
      'query_string' => false,
    ),
  'drivers' => array (
      'file' => array (
          'prefix' => '',
          'path' => FCPATH . 'cache' . DIRECTORY_SEPARATOR
        ),
      'redis' => array (
          'prefix' => 'cache:',
          'host' => 'localhost',
          'port' => '6379',
          'password' => null,
          'database' => null,
          'timeout' => null,
        ),
      'memcached' => array (
          'prefix' => 'cache:',
          'servers' => array (
              array (
                  'host' => 'localhost',
                  'port' => '11211',
                  'weight' => 0,
                )
            )
        )
    )
);