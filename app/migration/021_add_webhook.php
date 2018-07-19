<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "CREATE TABLE `webhooks` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `type` enum('commentCard', 'updateCheckItemStateOnCard', 'deleteCard') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'commentCard' COMMENT 'commentCard',
        `model_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'model ID',
        `servicer_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'trello人員ID',
        `content` text COLLATE utf8mb4_unicode_ci COMMENT '內容',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
        PRIMARY KEY (`id`),
        KEY `model_id_index` (`model_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE `webhooks`;",
    'at' => "2018-07-16 17:59:04",
  );
