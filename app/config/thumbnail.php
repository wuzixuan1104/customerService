<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

return array (
    'ThumbnailImagick' => array (
        'allow' => array ('gif', 'jpg', 'png'),
        'resize_up' => true
      ),
    'ThumbnailGd' => array (
        'allow' => array ('gif', 'jpg', 'png'),
        'resize_up' => true,
        'interlace' => null,
        'jpeg_quality' => 90,
        'preserve_alpha' => true,
        'preserve_transparency' => true,
        'alpha_maskColor' => array (255, 255, 255),
        'transparency_mask_color' => array (0, 0, 0)
      ),
  );