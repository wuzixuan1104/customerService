<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Admin extends Model {
  static $table_name = 'admins';

  static $has_one = array (
  );

  static $has_many = array (
    array ('roles',  'class_name' => 'AdminRole'),
  );

  static $belongs_to = array (
  );

  private static $current = null;

  public function __construct ($attrs = array (), $guardAttrs = true, $instantiatingViafind = false, $newRecord = true) {
    parent::__construct ($attrs, $guardAttrs, $instantiatingViafind, $newRecord);
  }


  public function is_root () {
    return $this->roles && in_array ('root', array_orm_column ($this->roles, 'role'));
  }
  public function in_roles ($roles = array (), $ignoreRoot = false) {
    if (!$this->roles) return false;
    if (!$ignoreRoot && $this->is_root ()) return true;
    if (!$roles = array_filter ($roles, function ($role) { return isset (AdminRole::$roleTexts[$role]); })) return false;

    foreach (array_orm_column ($this->roles, 'role') as $role)
      if (in_array ($role, $roles))
        return true;

    return false;
  }


  public static function current () {
    if (self::$current !== null)
      return self::$current;

    $token = Session::getData ('token');
    return self::$current = Admin::find_by_token ($token);
  }

  public function destroy () {
    if (!isset ($this->id))
      return false;

    return $this->delete ();
  }

  public function putFiles ($files) {
    foreach ($files as $key => $file)
      if (isset ($files[$key]) && $files[$key] && $this->$key instanceof Uploader && !$this->$key->put ($files[$key]))
        return false;
    return true;
  }
}
