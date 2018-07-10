<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Backup extends Model {
  static $table_name = 'backups';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
  );

  const STATUS_FAILURL = 'failure';
  const STATUS_SUCCESS = 'success';

  static $statusNames = array (
    self::STATUS_FAILURL => '失敗',
    self::STATUS_SUCCESS => '成功',
  );

  const TYPE_DATABASE  = 'datebase';
  const TYPE_QUERY_LOG = 'query-log';
  const TYPE_LOG       = 'log';
  const TYPE_OTHER     = 'other';

  static $typeNames = array (
    self::TYPE_DATABASE  => '資料庫',
    self::TYPE_QUERY_LOG => '查詢記錄',
    self::TYPE_LOG       => '一般 Log',
    self::TYPE_OTHER     => '其他',
  );

  const READ_YES  = 'yes';
  const READ_NO = 'no';

  static $readTexts = array (
    self::READ_YES  => '已讀',
    self::READ_NO => '未讀',
  );

  public function __construct ($attrs = array (), $guardAttrs = true, $instantiatingViafind = false, $newRecord = true) {
    parent::__construct ($attrs, $guardAttrs, $instantiatingViafind, $newRecord);

    // 設定檔案上傳器
    Uploader::bind ('file', 'BackupFileFileUploader');
  }

  public function destroy () {
    if (!isset ($this->id))
      return false;

    return $this->delete ();
  }

  public function putFiles ($files) {
    foreach ($files as $key => $file)
      if (isset ($files[$key]) && $files[$key] && isset ($this->$key) && $this->$key instanceof Uploader && !$this->$key->put ($files[$key]))
        return false;
    return true;
  }
}

/* -- 檔案上傳器物件 ------------------------------------------------------------------ */
class BackupFileFileUploader extends FileUploader {
}
