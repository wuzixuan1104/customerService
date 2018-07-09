<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Benchmark {
  private static $times = array ();
  private static $memories = array ();

  public static function init () {
    Benchmark::markStar ('整體');
    Benchmark::markStar ('核心');
  }

  public static function markStar ($key) {
    isset (self::$times[$key]) || self::$times[$key] = array ();
    isset (self::$memories[$key]) || self::$memories[$key] = array ();
    self::$times[$key]['s'] = microtime (true);
    self::$memories[$key]['s'] = memory_get_usage ();
  }
  public static function markEnd ($key) {
    isset (self::$times[$key]) || self::$times[$key] = array ();
    isset (self::$memories[$key]) || self::$memories[$key] = array ();
    self::$times[$key]['e'] = microtime (true);
    self::$memories[$key]['e'] = memory_get_usage ();
  }
  // public static function mark ($key) {
  //   self::$times[$key] = microtime (true);
  // }

  public static function elapsedTime ($key = null, $decimals = 4) {
    if (!$key) {
      $arr = [];
      foreach (self::$times as $key => $time)
        if (isset ($time['s']))
          $arr[$key] = number_format ((isset ($time['e']) ? $time['e'] : microtime (true)) - $time['s'], $decimals);
      return $arr;
    }
    if (!isset (self::$times[$key], self::$times[$key]['s']))
      return null;

    isset (self::$times[$key]['e']) || self::$times[$key]['e'] = microtime (true);

    return number_format (self::$times[$key]['e'] - self::$times[$key]['s'], $decimals);
  }

  public static function memoryUsage ($decimals = 4) {
    return round (memory_get_usage () / pow (1024, 2), 4) . 'MB';
  }
  public static function elapsedMemory ($key = null, $decimals = 4) {
    
    if (!$key) {
      $arr = [];
      foreach (self::$memories as $key => $memory)
        if (isset ($memory['s']))
          $arr[$key] = round (((isset ($memory['e']) ? $memory['e'] : memory_get_usage ()) - $memory['s']) / pow (1024, 2), $decimals) . 'MB';
      return $arr;
    }
    if (!isset (self::$memories[$key], self::$memories[$key]['s']))
      return null;

    isset (self::$memories[$key]['e']) || self::$memories[$key]['e'] = memory_get_usage ();

    return round ((self::$memories[$key]['e'] - self::$memories[$key]['s']) / pow (1024, 2), $decimals) . 'MB';
  }

  // public static function memory_usage () {
  //   return '{memory_usage}';
  // }
}
