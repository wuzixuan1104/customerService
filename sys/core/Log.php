<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Log {
  private static $type = null;
  private static $fps = array ();
  private static $lock = false;

  private static $config = array (
    'path' => FCPATH . 'log' . DIRECTORY_SEPARATOR,
    'extension' => '.log',
    'permissions' => 0777,
    'dateFormat' => 'Y-m-d H:i:s'
  );

  public static function info ($msg) {
    @self::message (self::formatLine (date (self::$config['dateFormat']), cc ('紀錄', 'g'), $msg), 'log-info-');
  }
  public static function warning ($msg) {
    @self::message (self::formatLine (date (self::$config['dateFormat']), cc ('警告', 'y'), $msg), 'log-warning-');
  }
  public static function error ($msg) {
    @self::message (self::formatLine (date (self::$config['dateFormat']), cc ('錯誤', 'r'), $msg), 'log-error-');
  }
  public static function queryLine () {
    self::$type || self::$type = ENVIRONMENT !== 'cmd' ? request_is_cli () ? cc ('cli', 'c') . cc (' ➜ ', 'N') . cc (URL::uriString (), 'C') : cc ('web', 'p') . cc (' ➜ ', 'N') . cc (URL::uriString (), 'P') : cc ('cmd', 'y') . cc (' ➜ ', 'N') . cc (CMD_FILE, 'Y');
    @self::message ("\n" . self::$type . cc (' ╞' . str_repeat ('═', CLI_LEN - (strlen (self::$type) - 31)) . "\n", 'N'), 'query-');
  }
  public static function query ($valid, $time, $sql, $values) {
    @self::message (self::formatQuery (date (self::$config['dateFormat']), $valid, $time, $sql, $values), 'query-');
  }

  public static function closeAll () {
    foreach (self::$fps as $fp)
      fclose ($fp);
  }

  private static function message ($msg, $prefix = 'log-') {
    if (!(is_dir (self::$config['path']) && is_really_writable (self::$config['path'])))
      return false;

    $newfile = !file_exists ($path = self::$config['path'] . $prefix . date ('Y-m-d') . self::$config['extension']);

    if (!isset (self::$fps[$path]))
      if (!$fp = @fopen ($path, FOPEN_WRITE_CREATE))
        return false;
      else
        self::$fps[$path] = $fp;

    Log::$lock && flock (self::$fps[$path], LOCK_EX);


    for ($written = 0, $length = Charset::strlen ($msg); $written < $length; $written += $result)
      if (($result = fwrite (self::$fps[$path], Charset::substr ($msg, $written))) === false)
        break;

    Log::$lock && flock (self::$fps[$path], LOCK_UN);

    $newfile && @chmod ($path, self::$config['permissions']);

    return is_int ($result);
  }

  private static function formatLine ($date, $title, $msg) {
    return cc ($date, 'w') . cc ('：', 'N') . $title . cc ('：', 'N') . $msg . "\n";
  }
  private static function formatQuery ($date, $valid, $time, $sql, $values) {
    self::$type || self::$type = ENVIRONMENT !== 'cmd' ? request_is_cli () ? cc ('cli', 'c') . cc (' ➜ ', 'N') . cc (URL::uriString (), 'C') : cc ('web', 'p') . cc (' ➜ ', 'N') . cc (URL::uriString (), 'P') : cc ('cmd', 'y') . cc (' ➜ ', 'N') . cc (CMD_FILE, 'Y');
    return self::$type . cc (' │ ', 'N') . cc ($date, 'w') . cc (' ➜ ', 'N') . cc ($time, $time < 999 ? $time < 99 ? $time < 9 ? 'w' : 'W' : 'Y' : 'R') . '' . cc ('ms', $time < 999 ? $time < 99 ? $time < 9 ? 'N' : 'w' : 'y' : 'r') . cc (' │ ', 'N') . ($valid ? cc ('OK', 'g') : cc ('GG', 'r')) . cc (' ➜ ', 'N') . call_user_func_array ('sprintf', array_merge (array (preg_replace_callback ('/\?/', function ($matches) { return cc ('%s', 'W'); }, $sql)), $values)) . "\n";
  }
}