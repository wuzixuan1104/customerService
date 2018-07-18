<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'up' => "ALTER TABLE `webhooks` ADD `response`  text COLLATE utf8mb4_unicode_ci COMMENT '回應' AFTER `content`;",
    'down' => "ALTER TABLE `webhooks` DROP COLUMN `response`;",
    'at' => "2018-07-18 11:37:37",
  );
