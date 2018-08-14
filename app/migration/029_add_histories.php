<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "CREATE TABLE `histories` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `card_id` int(11) unsigned NOT NULL,
        `servicer_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '當不等於0則為客服人員回覆',
        `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '內容',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
        PRIMARY KEY (`id`),
        KEY `card_id_index` (`card_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE `histories`;",
    'at' => "2018-08-14 13:49:36",
  );