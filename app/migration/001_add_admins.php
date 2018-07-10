<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "CREATE TABLE `admins` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱',
        `account` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '帳號',
        `password` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密碼',
        `token` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Access Token',

        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
        PRIMARY KEY (`id`),
        KEY `token_index` (`token`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'down' => "DROP TABLE `admins`;",

    'at' => "2018-03-02 00:40:07",
  );
