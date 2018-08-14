<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "ALTER TABLE `sources` ADD `card_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '目前當下處理的卡片' AFTER `process`;",
    'down' => "ALTER TABLE `sources` DROP COLUMN `card_id`;",
    'at' => "2018-08-14 13:49:20",
  );