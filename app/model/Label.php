<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Label extends Model {
  static $table_name = 'labels';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
  );
  const TAG_NEW = 'new';
  const TAG_DEAL = 'deal';
  const TAG_FINISH = 'finish';
  const TAG_IMMEDIATE = 'immediate';
  const TAG_DEADLINE = 'deadline';

  static $tagTexts = [
    self::TAG_NEW => '新new',
    self::TAG_DEAL => '處理中...',
    self::TAG_FINISH => '已完成',
    self::TAG_IMMEDIATE => '立即處理',
    self::TAG_DEADLINE => '已到期'
  ];

  public function __construct ($attrs = array (), $guardAttrs = true, $instantiatingViafind = false, $newRecord = true) {
    parent::__construct ($attrs, $guardAttrs, $instantiatingViafind, $newRecord);
  }

  public function destroy () {
    if (!isset ($this->id))
      return false;

    return $this->delete ();
  }
}
