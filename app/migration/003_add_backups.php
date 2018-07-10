<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "CREATE TABLE `backups` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,

      `file` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '檔案',
      `size` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '檔案大小(Byte)',
      `type` enum('datebase', 'query-log', 'log', 'other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other' COMMENT '類型',
      `status` enum('failure', 'success') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'failure' COMMENT '狀態',
      `read` enum('yes', 'no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no' COMMENT '是否已讀(yes/no)',
      `time_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '時間',

      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'down' => "DROP TABLE `backups`;",
    'at' => "2018-03-26 09:38:46",
  );


