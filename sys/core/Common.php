<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('config')) {
  function config () {
    static $files, $keys;

    if (!(($args = func_get_args ()) && ($fileName = array_shift ($args))))
      // gg ('')
      exit ('找不到該 Config 檔案：' . $fileName);

    if (isset ($keys[$key = $fileName . implode ('_', $args)]))
      return $keys[$key];

    isset ($files[$fileName]) || $files[$fileName] = file_exists ($path = APPPATH . 'config' . DIRECTORY_SEPARATOR . ENVIRONMENT . DIRECTORY_SEPARATOR . $fileName . EXT) || file_exists ($path = APPPATH . 'config' . DIRECTORY_SEPARATOR . $fileName . EXT) ? include_once ($path) : null;

    if ($files[$fileName] === null && !($keys[$key] = null))
      exit ('找不到該 Config 檔案：' . $fileName);

    $t = $files[$fileName];

    foreach ($args as $arg)
      if (!$t = isset ($t[$arg]) ? $t[$arg] : null)
        break;

    return $keys[$key] = $t;
  }
}

if (!function_exists ('remove_invisible_characters')) {
  function remove_invisible_characters ($str, $urlEncoded = true) {
    $n = array ();

    $urlEncoded && array_push ($n, '/%0[0-8bcef]/i', '/%1[0-9a-f]/i', '/%7f/i');

    array_push ($n, '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S');

    do {
      $str = preg_replace ($n, '', $str, -1, $count);
    } while ($count);

    return $str;
  }
}

if (!function_exists ('html_escape')) {
  function html_escape ($var, $doubleEncode = true) {
    if (!$var)
      return $var;

    if (!is_array ($var))
      return htmlspecialchars ($var, ENT_QUOTES, config ('other', 'charset'), $doubleEncode);

    foreach (array_keys ($var) as $key)
      $var[$key] = html_escape ($var[$key], $doubleEncode);

    return $var;
  }
}

if (!function_exists ('stringify_attributes')) {
  function stringify_attributes ($attrs, $js = false) {
    $atts = '';

    if (!$attrs)
      return $atts;
    
    if (is_string ($attrs))
      return ' ' . $attrs;
    
    if (!is_array ($attrs))
      return $atts;

    foreach ($attrs as $key => $val)
      $atts .= $js ? $key . '=' . $val . ',' : ' ' . $key . '="' . $val . '"';

    return rtrim ($atts, ',');
  }
}

if (!function_exists ('is_really_writable')) {
  function is_really_writable ($file) {
    if (DIRECTORY_SEPARATOR === '/' && (is_php ('5.4') || !ini_get ('safe_mode')))
      return is_writable ($file);

    if (is_dir ($file)) {
      if (($fp = @fopen ($file = rtrim ($file, '/') . '/' . md5 (mt_rand ()), 'ab')) === false)
        return false;
 
      fclose ($fp);
      @chmod ($file, 0777);
      @unlink ($file);

      return true;
    }

    if (!is_file ($file) || ($fp = @fopen ($file, 'ab')) === false)
      return false;
 
    fclose ($fp);
    return true;
  }
}

if (!function_exists ('request_is_https')) {
  function request_is_https () {
    return (!empty ($_SERVER['HTTPS']) && strtolower ($_SERVER['HTTPS']) !== 'off')
        || (isset ($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower ($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty ($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower ($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off');
  }
}

// if (!function_exists ('request_use_method')) {
//   function request_use_method () {
//     return strtolower (request_is_cli ()
//            ? 'cli'
//            : (isset ($_SERVER['REQUEST_METHOD'])
//              ? $_SERVER['REQUEST_METHOD']
//              : (isset ($_POST['_method'])
//                ? $_POST['_method']
//                : 'get')));
//   }
// }

if (!function_exists ('request_is_ajax')) {
  function request_is_ajax () {
    return isset ($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower ($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
  }
}

if (!function_exists ('array_2d_to_1d')) {
  function array_2d_to_1d ($array) {
    $messages = array ();
    foreach ($array as $key => $value)
      if (is_array ($value)) $messages = array_merge ($messages, $value);
      else array_push ($messages, $value);
    return $messages;
  }
}

if (!function_exists ('sort_2d_array')) {
  function sort_2d_array ($key, $list) {
    if ($list) {
      $tmp = array ();
      foreach ($list as &$ma) $tmp[] = &$ma[$key];
      array_multisort ($tmp, SORT_DESC, $list);
    }
    return $list;
  }
}

// gg (
//   '503 Service Unavailable.', array (
//     'font' => '⚠',
//     'text' => '錯誤',
//     'msg' => '503 Service Unavailable'
//     ), array (
//     'quote' => '不存在的 $system_path',
//     'detail' => array (
//         '物件' => 'ActiveRecord\DatabaseException',
//         '訊息' => 'SQLSTATE[HY000] [1049] Unknown database "oaciw"',
//         '檔案' => '/Users/OA/www/ci316/sys/model/lib/Connection.php(262)'
//       ),
//     'traces' => array (
//         'sys/model/lib/Connection.php(122)' => 'ActiveRecord\Connection->__construct(stdClass)',
//         'sys/model/lib/Connection.php(12)' => 'ActiveRecord\Connection->__construct(stdClass)',
//         'sys/model/lib/Connection.php(2)' => 'ActiveRecord\Connection->__construct(stdClass)'
//       )
//     ), array (
//       'string' => 'HTTP/1.1 503 Service Unavailable.',
//       'replace' => true,
//       'code' => '503'
//     ));