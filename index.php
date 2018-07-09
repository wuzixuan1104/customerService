<?php

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

date_default_timezone_set ('Asia/Taipei');

define ('OACI', '1');
define ('CLI_LEN', 80);

define ('EXT', '.php');
defined ('STDIN') && chdir (dirname (__FILE__));
define ('SELF', pathinfo (__FILE__, PATHINFO_BASENAME));
define ('FCPATH', dirname (__FILE__) . DIRECTORY_SEPARATOR);

$sys_dir  = 'sys';
$app_dir  = 'app';
$view_dir = 'app' . DIRECTORY_SEPARATOR . 'view';

(@include_once $sys_dir . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Init.php') || exit ('初始化失敗！');

is_dir ($sys_dir  = realpath ($sys_dir))  || gg ('您的 sys 資料夾路徑似乎沒有正確設置！',  503, array ('detail' => array ('檔案' => SELF, '變數' => '$sys_dir',  '內容' => $sys_dir)));
is_dir ($app_dir  = realpath ($app_dir))  || gg ('您的 app 資料夾路徑似乎沒有正確設置！',  503, array ('detail' => array ('檔案' => SELF, '變數' => '$app_dir',  '內容' => $app_dir)));
is_dir ($view_dir = realpath ($view_dir)) || gg ('您的 view 資料夾路徑似乎沒有正確設置！', 503, array ('detail' => array ('檔案' => SELF, '變數' => '$view_dir', '內容' => $view_dir)));

define ('BASEPATH', $sys_dir . DIRECTORY_SEPARATOR);
define ('APPPATH', $app_dir . DIRECTORY_SEPARATOR);
define ('VIEWPATH', $view_dir . DIRECTORY_SEPARATOR);

(@include_once BASEPATH . 'core' . DIRECTORY_SEPARATOR . 'Load.php') || gg ('初始化失敗！', 503);
Load::file ('_env.php', true);

switch (ENVIRONMENT) {
  case 'development':
    ini_set ('display_errors', 1);
    error_reporting (-1);
    break;

  case 'production':
    ini_set ('display_errors', 0);
    error_reporting (is_php ('5.3') ? E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED : E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
    break;

  default:
    gg ('您的 環境變數 設定不在選項內或設定錯誤！', 503, array ('detail' => array ('檔案' => SELF, '變數' => 'ENVIRONMENT', '內容' => ENVIRONMENT)));
   break;
}

require_once BASEPATH . 'core' . DIRECTORY_SEPARATOR . 'CodeIgniter.php';

// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump (Benchmark::elapsedTime (), Benchmark::elapsedMemory (), Benchmark::memoryUsage ());
// exit ();