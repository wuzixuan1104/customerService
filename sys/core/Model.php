<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('use_model')) {
  function use_model () {

    static $used;

    if (!empty ($used))
      return true;

    if (!$database = config ('database'))
      return false;

    Load::file (BASEPATH . 'model' . DIRECTORY_SEPARATOR . 'ActiveRecord.php', true);

    ActiveRecord\Config::initialize (function ($cfg) use ($database) {
      $cfg->set_model_directory (APPPATH . 'model')
          ->set_connections (array_combine (array_keys ($database['groups']), array_map (function ($group) { return $group['dbdriver'] . '://' . $group['username'] . ':' . $group['password'] . '@' . $group['hostname'] . '/' . $group['database'] . '?charset=' . $group['char_set']; }, $database['groups'])), $database['active_group']);

      ($cacheConfig = config ('model', 'cache')) && isset ($cacheConfig['enable'], $cacheConfig['driver']) && $cacheConfig['enable'] && Load::sysLib ('Cache.php') && $cfg->set_cache ($cacheConfig['driver'], isset ($cacheConfig['prefix']) ? $cacheConfig['prefix'] : null, isset ($cacheConfig['expire']) ? $cacheConfig['expire'] : null);

      class_exists ('Log') && $cfg->setLog ('Log') && Log::queryLine ();
    });

    class_alias ('ActiveRecord\Connection', 'ModelConnection');
    
    class Model extends ActiveRecord\Model {
      
      public static function create ($attributes, $validate = true, $guard_attributes = true) {
        $attributes = array_intersect_key ($attributes, self::table ()->columns);
        return parent::create ($attributes, $validate, $guard_attributes);
      }

      public function columnsUpdate ($arr = array ()) {
        if ($columns = array_intersect_key ($arr, $this->table ()->columns))
          foreach ($columns as $column => $value)
            $this->$column = $value;
        return true;
      }
      public static function getArray ($column, $option = array ()) {
        return array_orm_column (self::find ('all', array_merge ($option, array ('select' => $column))), $column);
      }
      public static function getTransactionError ($closure, &...$args) {
        if (!is_callable ($closure))
          return false;

        $class = get_called_class ();
        return  call_user_func_array (array ($class, 'transaction'), array_merge (array ($closure), $args)) ? null : '資料庫處理錯誤！';
      }
    }

    spl_autoload_register (function ($class) {
      if (class_exists ($class, false))
        return;

      if (preg_match ("/Uploader$/", $class))
        Load::sysLib ('Uploader' . EXT) || gg ('找不到 Model 相關工具：' . $class);

      if ($class === 'Where')
        Load::sysLib ('Where' . EXT) || gg ('找不到 Model 相關工具：' . $class);
    });

    // Load::sysLib ('Uploader.php');
    // Load::sysLib ('Where.php');

    if (!function_exists ('array_orm_column')) {
      function array_orm_column ($arr, $key) {
        return array_map (function ($t) use ($key) {
          is_callable ($key) && $key = $key ();
          return $t->$key;
        }, $arr);
      }
    }
    return $used = true;
  }
}

config ('model', 'auto_load') && use_model ();