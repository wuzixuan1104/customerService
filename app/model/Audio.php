<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Audio extends Model {
  static $table_name = 'audios';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
  );

  public function __construct ($attrs = array (), $guardAttrs = true, $instantiatingViafind = false, $newRecord = true) {
    parent::__construct ($attrs, $guardAttrs, $instantiatingViafind, $newRecord);

    // 設定檔案上傳器
    Uploader::bind ('file', 'AudioFileFileUploader');
  }

  public function destroy () {
    if (!isset ($this->id))
      return false;

    return $this->delete ();
  }
}

/* -- 檔案上傳器物件 ------------------------------------------------------------------ */
class AudioFileFileUploader extends FileUploader {
}
