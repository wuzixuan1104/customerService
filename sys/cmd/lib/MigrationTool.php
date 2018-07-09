<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class MigrationTool {
  private static $model;
  private static $path;
  private static $gets;
  private static $files;

  public static function init () {
    Load::sysCore ('Model.php', true);
    Load::sysFunc ('file.php', true);

    use_model () || gg ('[Migration] init 錯誤，無法連線資料庫。');

    self::$gets = array ();
    self::$files = null;
    $config = config ('migration');

    isset ($config['model']) && class_exists ($model = $config['model']) || gg ('[Migration] init 錯誤，找不到指定的 Model。Model：' . $config['model']);
    isset ($config['path']) && is_readable (self::$path = $config['path']) || gg ('[Migration] init 錯誤，找不到指定的 Migration 路徑。Path：' . $config['path']);

    if (!(($obj = ModelConnection::instance ()->query ("SHOW TABLES LIKE '" . $model::$table_name . "';")->fetch (PDO::FETCH_NUM)) && ($obj[0] == $model::$table_name)))
      self::createTable ($model);

    self::$model || (self::$model = $model::find ('first')) || (self::$model = self::createModel ($model)) || gg ('[Migration] init 錯誤，初始化失敗。');
  }

  private static function query ($sql) {
    try {
      ModelConnection::instance ()->query ($sql);
      return '';
    } catch (Exception $e) {
      return $e->getMessage ();
    }
  }

  private static function createModel ($model) {
    return $model::create (array ('version' => 0)); 
  }

  private static function createTable ($model) {
    ($database = config ('database')) && isset ($database['active_group']) && ($active = $database['active_group']) && isset ($database['groups'][$active]['char_set']) && ($char_set = $database['groups'][$active]['char_set']) && isset ($database['groups'][$active]['dbcollat']) && ($dbcollat = $database['groups'][$active]['dbcollat']) || gg ('[Migration] createTable 錯誤，Database Config 錯誤。');

    $sql = "CREATE TABLE `" . $model::$table_name . "` ("
           . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
           . "`version` varchar(14) NOT NULL DEFAULT '0' COMMENT '版本',"
           . "`updated_at` datetime NOT NULL DEFAULT '" . date ('Y-m-d H:i:s') . "' COMMENT '更新時間',"
           . "`created_at` datetime NOT NULL DEFAULT '" . date ('Y-m-d H:i:s') . "' COMMENT '新增時間',"
           . "PRIMARY KEY (`id`)"
         . ") ENGINE=InnoDB DEFAULT CHARSET=" . $char_set . " COLLATE=" . $dbcollat . ";";

    ($err = self::query ($sql)) && gg ('[Migration] createTable 錯誤，建置 Table 失敗。SQL：' . $sql . '，Error：' . $err);

    return self::$model = self::createModel ($model);
  }

  public static function nowVersion () {
    return $now = (int)self::$model->version;
  }
  public static function files ($re = false) {
    if (!$re && self::$files !== null)
      return self::$files;
    $files = array_filter (array_map (function ($file) { return file_exists ($file) && is_readable ($file) && ($name = basename ($file, '.php')) && preg_match ('/^\d{3}_(.+)$/', $name) && ($v = sscanf ($name, '%[0-9]+', $number) ? $number : 0) ? array ((int) $v, $file) : null; }, glob (self::$path . '*_*.php')));
    $files = array_combine (array_column ($files, 0), array_column ($files, 1));
    ksort ($files);
    return self::$files = $files;
  }

  public static function get ($file, $isUp = null) {
    if (isset (self::$gets[$file]))
      return $isUp !== null ? $isUp ? self::$gets[$file]['up'] : self::$gets[$file]['down'] : self::$gets[$file];
    
    $data = include_once ($file);

    isset ($data['up']) && (is_string ($data['up']) || is_array ($data['up'])) && isset ($data['at']) && is_string ($data['at']) && isset ($data['down']) && (is_string ($data['down']) || is_array ($data['down'])) || gg ('[Migration] Struct 錯誤，檔案結構格式錯誤，up、down 以及 at 功能有缺。File：' . $file);
    
    $data['up'] = is_string ($data['up']) ? array ($data['up']) : $data['up'];
    $data['down'] = is_string ($data['down']) ? array ($data['down']) : $data['down'];

    self::$gets[$file] = $data;
    return $isUp !== null ? $isUp ? self::$gets[$file]['up'] : self::$gets[$file]['down'] : self::$gets[$file];
  }

  private static function run ($tmps, $isUp, $to) {
    $last = !$isUp && $to ? array_pop ($tmps) : null;

    foreach ($tmps as $file) {
      foreach (self::get ($file[1], $isUp) as $sql)
        if ($sql && ($err = self::query ($sql)))
          return array ("SQL 語法：" . $sql, '錯誤原因：' . $err);
        // && gg ('[Migration] run 錯誤，檔案格式內容有誤。SQL：' . $sql . '，Error：' . $err);
      
      self::$model->version = $file[0];
      self::$model->save ();
    }
    
    if ($isUp)
      return true;

    $version = $last ? $last[0] : 0;

    self::$model->version = $version;
    self::$model->save ();

    return true;
  }

  public static function to ($to = null) {
    $now = self::nowVersion ();
    $files = self::files ();

    $tmps = array_keys ($files);
    $to !== null || $to = end ($tmps);

    if ($to == $now)
      return true;

    $tmps = array ();

    if ($isUp = $to > $now) foreach ($files as $version => $file) $version > $now && $version <= $to && array_push ($tmps, array ($version, $file));
    else foreach ($files as $version => $file) $version <= $now && $version >= $to && array_unshift ($tmps, array ($version, $file));

    return self::run ($tmps, $isUp, $to);
  }

  public static function create ($name, &$err = '') {
    is_really_writable (self::$path) || gg ('[Migration] create 錯誤，路徑無分寫入。Path：' . self::$path);

    $files = array_keys (self::files ());
    $version = $files ? end ($files) + 1 : 1;

    file_exists ($path = self::$path . sprintf ('%03s_%s.php', $version, $name)) && gg ('[Migration] create 錯誤，檔案已經存在。Path：' . $path);

    $content = "<?php defined ('OACI') || exit ('此檔案不允許讀取。');\n" . "\n" . "/**\n" . " * @author      OA Wu <comdan66@gmail.com>\n" . " * @copyright   Copyright (c) 2013 - " . date ('Y') . ", OACI\n" . " * @license     http://opensource.org/licenses/MIT  MIT License\n" . " * @link        https://www.ioa.tw/\n" . " */\n" . "\n" . "return array (\n" . "    'up' => \"\",\n" . "    'down' => \"\",\n" . "    'at' => \"" . date ('Y-m-d H:i:s') . "\",\n" . "  );";

    $err = $path;

    if (write_file ($path, $content))
      return true;

    $err = '產生檔案失敗。Path：' . $path;

    return false;
  }
}
