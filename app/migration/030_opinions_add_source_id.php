<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "ALTER TABLE `opinions` ADD `source_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '來源ID' AFTER `id`;",
    'down' => "ALTER TABLE `opinions` DROP COLUMN `source_id`;",
    'at' => "2018-08-21 09:56:13",
  );