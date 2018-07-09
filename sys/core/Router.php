<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Router {
  private static $directories;
  private static $class;
  private static $method;
  private static $params;
  static $routers;
  static $router;

  public static function init ($routing = null) {
    self::$router = self::$class = self::$method = null;
    self::$routers = self::$params = self::$directories = array ();

    Load::file (APPPATH . 'config' . DIRECTORY_SEPARATOR . 'router.php', true);

    self::parseRouters ();
  }
 
  public static function dir ($prefix, $callback) {
    $callback ();
  }
  private static function getDirs () {
    if (($dir = array_filter (array_map (function ($trace) {
                  return isset ($trace['class']) && ($trace['class'] == 'Router') && isset ($trace['function']) && ($trace['function'] == 'dir') && isset ($trace['type']) && ($trace['type'] == '::') && isset ($trace['args'][0]) ? $trace['args'][0] : null;
                }, debug_backtrace (DEBUG_BACKTRACE_PROVIDE_OBJECT)))) && ($dir = array_shift ($dir)))
      return ($t = trim ($dir, '/')) ? is_dir (APPPATH . 'controller' . DIRECTORY_SEPARATOR . str_replace ('/', DIRECTORY_SEPARATOR, $t)) ? explode ('/', $t) : gg ('Router dir 設定錯誤，不存在的目錄：' . $t) : array ();
    else
      return array ();
  }
  public static function restful ($uris, $controller, $models) {
    class_exists ('RestfulUrl', false) || Load::sysLib ('RestfulUrl.php', true);

    is_array ($uris) || $uris = array ($uris);
    is_array ($models) || $models = array ($models);
    $c = count ($uris);

    $t1 = $c > 1 ? ', ' . implode (', ', array_map (function ($a) { return '$' . $a; }, range (1, $c - 1))) : '';
    $t2 = $c > 1 ? ', ' . implode (', ', array_map (function ($a) { return '$' . $a; }, range (2, $c))) : '';

    $prefixs = implode ('/', array_merge (self::getDirs (), array ($controller)));
    RestfulUrl::addGroup ($prefixs, 'index', self::method ('get', explode ('/', implode ('/(:id)/', $uris)), $controller . '@index(' . $t1 . ')', $models));
    RestfulUrl::addGroup ($prefixs, 'show', self::method ('get', explode ('/', implode ('/(:id)/', $uris) . '/(:id)'), $controller . '@show($1' . $t2 . ')', $models));
    RestfulUrl::addGroup ($prefixs, 'add', self::method ('get', explode ('/', implode ('/(:id)/', $uris) . '/add'), $controller . '@add(' . $t1 . ')', $models));
    RestfulUrl::addGroup ($prefixs, 'create', self::method ('post', explode ('/', implode ('/(:id)/', $uris)), $controller . '@create(' . $t1 . ')', $models));
    RestfulUrl::addGroup ($prefixs, 'edit', self::method ('get', explode ('/', implode ('/(:id)/', $uris) . '/(:id)/edit'), $controller . '@edit($1' . $t2 . ')', $models));
    RestfulUrl::addGroup ($prefixs, 'update', self::method ('put', explode ('/', implode ('/(:id)/', $uris) . '/(:id)'), $controller . '@update($1' . $t2 . ')', $models));
    RestfulUrl::addGroup ($prefixs, 'destroy', self::method ('delete', explode ('/', implode ('/(:id)/', $uris) . '/(:id)'), $controller . '@destroy($1' . $t2 . ')', $models));

    RestfulUrl::addGroup ($prefixs, '', self::method ('get', explode ('/', implode ('/(:id)/', $uris) . '/(:id)/(:any)'), $controller . '@$' . ($c + 1) . '($1' . $t2 . ')', $models));
    RestfulUrl::addGroup ($prefixs, '', self::method ('post', explode ('/', implode ('/(:id)/', $uris) . '/(:id)/(:any)'), $controller . '@$' . ($c + 1) . '($1' . $t2 . ')', $models));
    
    // RestfulUrl::addGroup ($prefixs, 'sorts', self::method ('get', explode ('/', implode ('/(:id)/', $uris) . '/sorts'), $controller . '@sorts(' . $t1 . ')', $models));
    RestfulUrl::addGroup ($prefixs, 'sorts', self::method ('post', explode ('/', implode ('/(:id)/', $uris) . '/sorts'), $controller . '@sorts(' . $t1 . ')', $models));
  }
  private static function method ($m, $formats, $uri, $models = array ()) {
    $prefixs = self::getDirs ();
    $formats = array_filter ($formats, function ($format) { return $format !== ''; });

    $uri = preg_split ('/[@,\(\)\s]+/', $uri);

    $controller = array_shift ($uri);
    $method = array_shift ($uri);
    $params = array_filter ($uri, function ($param) { return $param !== null && $param !== ''; });
    $position = array_merge ($prefixs, array ($controller, $method), $params);

    array_push (self::$routers, array (
        'method' => $m,
        'format' => $return = str_replace (array (':any', ':num', ':id'), array ('[^/]+', '[0-9]+', '[0-9]+'), implode ('/', array_merge ($prefixs, $formats))),
        'position' => implode ('/', $position),
        'params' => count ($prefixs) + 2,
        'models' => $models,
        'group' => array_merge ($prefixs, array ($controller))
      ));

    return $return;
  }

  public static function __callStatic ($name, $arguments) {
    in_array ($name == strtolower ($name), array ('get', 'post', 'put', 'delete', 'cli')) || gg ('Router 沒有此「' . $name . '」Method！');
    return self::method ($name, array (array_shift ($arguments)), array_shift ($arguments), array_shift ($arguments));
  }

  private static function parseRouters () {
    $uri = implode ('/', URL::segments ());

    $method = strtolower (request_is_cli () ? 'cli' : (isset ($_POST['_method']) ? $_POST['_method'] : (isset ($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'get')));

    foreach (self::$routers as $router)
      if ($router['method'] == $method && preg_match ('#^' . $router['format'] . '$#', $uri, $matches)) {
        strpos ($router['position'], '$') !== false && strpos ($router['format'], '(') !== false && $router['position'] = preg_replace ('#^' . $router['format'] . '$#', $router['position'], $uri);

        $position = explode ('/', $router['position']);

        self::$router = $router;
        self::$routers = array_slice ($position, self::$router['params']);
        self::$router['params'] = array ();

        foreach (self::$routers as $i => $id)
          isset (self::$router['models'][$i]) ? array_push (self::$router['params'], array (self::$router['models'][$i], $id)) : gg ('請確認 Router 的 RestfulUrl Model 數量設定是否正確。');

        unset (self::$router['models']);

        return self::setRequest ($position);
      }

    self::setRequest (URL::segments ());
  }
  private static function setRequest ($segments) {
    if (!$segments = self::validateRequest ($segments))
      return ;

    self::setClass (array_shift ($segments));
    self::setMethod (array_shift ($segments));
    
    self::setParams ($segments);
  }
  private static function validateRequest ($segments) {
    $c = count ($segments = array_values (array_filter ($segments, function ($segment) { return $segment !== null && $segment !== ''; })));

    while ($c-- > 0)
      if (($test = self::getDirectory () . str_replace ('-', '_', $segments[0])) && !file_exists (APPPATH . 'controller' . DIRECTORY_SEPARATOR . $test . EXT) && is_dir (APPPATH . 'controller' . DIRECTORY_SEPARATOR . self::getDirectory () . $segments[0]) && self::appendDirectory (array_shift ($segments)))
        continue;
      else
        return $segments;

    return $segments;
  }
  public static function getDirectory () {
    return implode (DIRECTORY_SEPARATOR, self::$directories) . (self::$directories ? DIRECTORY_SEPARATOR : '');
  }
  public static function appendDirectory ($dir) {
    return array_push (self::$directories, $dir);
  }
  public static function setClass ($class) {
    return self::$class = $class;
  }
  public static function setMethod ($method) {
    return self::$method = $method ? $method : 'index';
  }
  public static function setParams ($params) {
    return self::$params = $params;
  }
  public static function getClass () {
    return self::$class;
  }
  public static function getMethod () {
    return self::$method;
  }
  public static function getParams () {
    return self::$params;
  }
}
