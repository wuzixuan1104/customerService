<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "CREATE TABLE `sources` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `sid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '來源 ID',
        `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '標題',
        `type` enum('user', 'group', 'room', 'other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other' COMMENT '狀態，1 使用者，2 群組，3 聊天室',
        `process` TEXT COLLATE utf8mb4_unicode_ci COMMENT '動作',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
        PRIMARY KEY (`id`),
        KEY `sid_index` (`sid`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE `sources`;",
    'at' => "2018-04-18 16:25:00",
  );
