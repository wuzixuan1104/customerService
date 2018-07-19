<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "ALTER TABLE `cards` ADD `alert` enum('none', 'immediate', 'deadline') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none' COMMENT '警急狀態' AFTER `code`;",
    'down' => "ALTER TABLE `cards` DROP COLUMN `alert`;",
    'at' => "2018-07-19 13:55:06",
  );
