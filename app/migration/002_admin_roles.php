<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "CREATE TABLE `admin_roles` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `admin_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT 'User ID(作者)',
      `role` enum('root', 'admin', 'manager') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manager' COMMENT '角色',
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '新增時間',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'down' => "DROP TABLE `admin_roles`;",
    'at' => "2018-03-27 09:32:33",
  );
