<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class AdminRole extends Model {
  static $table_name = 'admin_roles';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
  );

  const ROLE_ROOT = 'root';
  const ROLE_ADMIN = 'admin';
  const ROLE_MANAGER = 'manager';

  static $roleTexts = array (
    self::ROLE_ROOT  => '最高權限',
    self::ROLE_ADMIN  => '後台管理者',
    self::ROLE_MANAGER  => '管理者',
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
