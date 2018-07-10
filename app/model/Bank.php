<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Bank extends Model {
  static $table_name = 'banks';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
  );

  const ENABLE_ON = 'on';
  const ENABLE_OFF = 'off';

  static $roleTexts = array (
    self::ENABLE_ON  => '啟動',
    self::ENABLE_OFF  => '關閉',
  );

  public function __construct ($attrs = array (), $guardAttrs = true, $instantiatingViafind = false, $newRecord = true) {
    parent::__construct ($attrs, $guardAttrs, $instantiatingViafind, $newRecord);
  }

  public function destroy () {
    if (!isset ($this->id))
      return false;

    return $this->delete ();
  }
}
