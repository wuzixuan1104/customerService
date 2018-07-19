<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Webhook extends Model {
  static $table_name = 'webhooks';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
  );

  const TYPE_COMMENT_CARD = 'commentCard';
  const TYPE_UPDATE_CHECK_ITEM_STATE_ON_CARD = 'updateCheckItemStateOnCard';
  const TYPE_DELETE_CARD = 'deleteCard';

  static $typeTexts = [
    self::TYPE_COMMENT_CARD => '評論卡片',
    self::TYPE_UPDATE_CHECK_ITEM_STATE_ON_CARD => '更新卡片上的列表狀態',
    self::TYPE_DELETE_CARD => '刪除卡片',
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
