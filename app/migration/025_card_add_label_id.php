<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "ALTER TABLE `cards` ADD `label_ids` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'label ID' AFTER `source_id`;",
    'down' => "ALTER TABLE `cards` DROP COLUMN `label_ids`;",
    'at' => "2018-07-19 13:55:06",
  );
