<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Cache {
  private static $drivers = array (
      'file' => null,
      'redis' => null,
      'memcached' => null,
    );

  public static function initialize ($driver, $config = array ()) {
    if (isset (self::$drivers[$driver]))
      return self::$drivers[$driver];

    if (!in_array ($driver, array_keys (self::$drivers)))
      return null;

    if (!Load::sysLib ('CacheDrivers' . DIRECTORY_SEPARATOR . ucfirst ($driver) . '.php'))
      return null;

    if (!class_exists ($class = 'Cache' . ucfirst ($driver) . 'Driver'))
      return null;

    return self::$drivers[$driver] = new $class ($config);
  }

  public static function __callStatic ($method, $args = array ()) {
    if (!$args)
      return null;

    $key = array_shift ($args);
    if (($closure = array_shift ($args)) === null) return null;
    is_numeric ($expire = array_shift ($args)) || $expire = 60;

    if (!(($class = self::initialize ($method)) && (is_callable (array ($class, 'get')) && is_callable (array ($class, 'save')))))
      return is_callable ($closure) ? $closure () : $closure;

    if (($data = $class->get ($key)) !== null)
      return $data;

    $class->save ($key, $data = is_callable ($closure) ? $closure () : $closure, $expire);

    return $data;
  }
}
