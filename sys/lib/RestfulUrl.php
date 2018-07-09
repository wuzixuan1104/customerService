<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class RestfulUrl {
  private static $list = array ();
  private static $urls = null;

  public static function addGroup ($controller, $action, $format) {
    if (!$action) return;
    isset (self::$list[$controller]) || self::$list[$controller] = array ();
    self::$list[$controller][$action] = $format;
  }
  public static function exception ($err) {
    throw new Exception ($err);
  }
  public static function url () {
    $params = func_get_args ();

    $key = array_shift ($params);
    $key = explode ('@', $key);
    $file = array_shift ($key);
    $method = array_shift ($key);

    if (!isset (self::$list[$file]))
      throw new Exception ('RestfulUrl 設定 url 錯誤！');

    if (!isset (self::$list[$file][$method]))
      self::$list[$file][$method] = self::$list[$file]['show'] . '/' . $method;
    
    $params = array_orm_column ($params, 'id');

    $i = -1;
    return URL::base (preg_replace_callback ('/\(\[0\-9\]\+\)/', function ($matches) use ($params, &$i) {
      return isset ($params[++$i]) ? $params[$i] : '%s';
    }, self::$list[$file][$method]));
  }

  public static function setUrls ($key, $parents) {
    
    if (!isset (self::$list[$key]))
      throw new Exception ('RestfulUrl 設定 setUrls 錯誤！');

    $keys = array_keys (self::$list[$key]);

    return self::$urls = array_combine ($keys, array_map (function ($method, $value) use ($key, $parents) {
      return call_user_func_array (array ('RestfulUrl', 'url'), array_merge (array ($key . '@' . $method), $parents));
    }, $keys, self::$list[$key]));
  }

  public static function __callStatic ($name, $arguments) {
    
    if (in_array ($name, array ('index', 'add', 'create', 'sorts')))
      if (isset (self::$urls[$name]))
        return self::$urls[$name];
      else
        throw new Exception ('RestfulUrl 錯誤，尚未設定 setUrls，method：' . $name);

    if (in_array ($name, array ('show', 'edit', 'update', 'destroy')))
      if ($arguments = array_shift ($arguments))
        if ((is_object ($arguments) && isset ($arguments->id)) || is_string ($arguments) || is_numeric ($arguments))
          return sprintf (self::$urls[$name], is_object ($arguments) ? $arguments->id : $arguments);
        else
          throw new Exception ('RestfulUrl::' . $name . '() 予物件或 ID 格式錯誤');
      else
        throw new Exception ('RestfulUrl::' . $name . '() 請給予物件或 ID');
  }
}