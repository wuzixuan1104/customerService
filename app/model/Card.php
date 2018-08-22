<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Card extends Model {
  static $table_name = 'cards';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
    array('source', 'class_name' => 'Source'),
    array('list', 'class_name' => 'TList', 'foreign_key' => 'list_id'),
  );

  const STATUS_NEW = 'new';
  const STATUS_YET = 'yet';
  const STATUS_DEAL = 'deal';
  const STATUS_FINISH = 'finish';

  const ALERT_NONE = 'none';
  const ALERT_IMMEDIATE = 'immediate';
  const ALERT_DEADLINE = 'deadline';

  static $statusTexts = array (
    self::STATUS_NEW  => '新new',
    self::STATUS_YET => '尚未處理',
    self::STATUS_DEAL  => '處理中',
    self::STATUS_FINISH  => '已完成',
  );

  static $alertTexts = array(
    self::ALERT_NONE => '無',
    self::ALERT_IMMEDIATE => '立即處理',
    self::ALERT_DEADLINE => '已到期',
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
