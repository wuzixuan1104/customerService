<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
  'tmp_dir' => FCPATH . 'tmp' . DIRECTORY_SEPARATOR,

  'thumbnail' => array (
      'separate_symbol' => '_',
      'auto_add_format' => true,
      'default_version' => array ('' => array ()),
      'driver' => 'Imagick', // Imagick 、 Gd
    ),

  'driver' => 'local', // local 、 s3

  'drivers' => array (
      'local' => array (
          'base_dir' => array ('upload'),
          'base_url' => 'http://dev.ximen.wifi/',
          'd4_url' => '',
        ),
      's3' => array (
          'bucket' => 'bucket',
          'access_key' => '',
          'secret_key' => '',

          'base_dir' => array ('uploads'),
          'base_url' => 'http://s3-ap-northeast-1.amazonaws.com/bucket/',

          'd4_url' => '',
        ),
    )
  );