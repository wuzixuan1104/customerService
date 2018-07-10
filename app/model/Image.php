<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Image extends Model {
  static $table_name = 'images';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
  );

  public function __construct ($attrs = array (), $guardAttrs = true, $instantiatingViafind = false, $newRecord = true) {
    parent::__construct ($attrs, $guardAttrs, $instantiatingViafind, $newRecord);
    // 設定圖片上傳器
    Uploader::bind ('file', 'ImageFileImageUploader');
  }

  public function destroy () {
    if (!isset ($this->id))
      return false;

    return $this->delete ();
  }
}

/* -- 圖片上傳器物件 ------------------------------------------------------------------ */
class ImageFileImageUploader extends ImageUploader {
  public function d4Url () {
    return Asset::url ('assets/img/d4.jpg');
  }
  public function getVersions () {
    return array (
        '' => array (),
        'w500' => array ('resize', 500, 500, 'width'),
        'c1200x630' => array ('adaptiveResizeQuadrant', 1200, 630, 't')
      );
  }
}
