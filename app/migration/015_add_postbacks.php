<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "CREATE TABLE `postbacks` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `source_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT 'Source ID',
        `speaker_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT 'Speaker Source ID',
        `reply_token` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '回覆 Token',
        `data` text NOT NULL COMMENT 'Data',
        `params` text NOT NULL COMMENT 'Params',
        `timestamp` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '時間',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'down' => "DROP TABLE `postbacks`;",
    'at' => "2018-04-23 13:49:02",
  );
