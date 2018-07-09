<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Input {
  private static $hasSanitizeGlobals;
  private static $headers;
  private static $ip;
  private static $inputStream;

  const PUT_FORM_DATA        = '_p_f_d';
  const PUT_X_WWW_URLENCODED = '_p_x_w_u';
  const PUT_RAW_TEXT         = '_p_r_t';
  const PUT_RAW_JSON         = '_p_r_j';
  
  public static function init () {
    self::$headers = null;
    self::$ip = null;
    self::$inputStream = null;
    self::$hasSanitizeGlobals = false;
  }

  private static function sanitizeGlobals () {
    if (self::$hasSanitizeGlobals)
      return;

    foreach ($_GET as $key => $val)
      $_GET[self::cleanInputKeys ($key)] = self::cleanInputData ($val);

    if (is_array ($_POST))
      foreach ($_POST as $key => $val)
        $_POST[self::cleanInputKeys ($key)] = self::cleanInputData ($val);

    if (is_array ($_COOKIE)) {
      unset ($_COOKIE['$Version'], $_COOKIE['$Path'], $_COOKIE['$Domain']);

      foreach ($_COOKIE as $key => $val)
        if (($cookieKey = self::cleanInputKeys ($key)) !== false) $_COOKIE[$cookieKey] = self::cleanInputData ($val);
        else unset ($_COOKIE[$key]);
    }

    $_SERVER['PHP_SELF'] = strip_tags ($_SERVER['PHP_SELF']);

    self::$hasSanitizeGlobals = true;
  }
  private static function cleanInputKeys ($str, $fatal = true) {
    if (!preg_match ('/^[a-z0-9:_\/|-]+$/i', $str))
      if ($fatal === true) {
        return false;
      } else {
        setStatusHeader (503);
        echo '有不合法的字元！';
        exit (7); // EXIT_USER_INPUT
      }

    if (UTF8_ENABLED === true)
      return Utf8::cleanString ($str);

    return $str;
  }
  private static function cleanInputData ($str) {
    if (is_array ($str)) {
      $t = array ();
      foreach (array_keys ($str) as $key)
        $t[self::cleanInputKeys ($key)] = self::cleanInputData ($str[$key]);
      return $t;
    }

    if (!is_php ('5.4') && get_magic_quotes_gpc ())
      $str = stripslashes ($str);

    if (UTF8_ENABLED === true)
      $str = Utf8::cleanString ($str);

    $str = remove_invisible_characters ($str, false);

    return preg_replace ('/(?:\r\n|[\r\n])/', PHP_EOL, $str);
  }

  private static function fetchFromArray (&$array, $index = null, $xssClean = null) {
    self::sanitizeGlobals ();

    $index = $index === null ? array_keys ($array) : $index;

    if (is_array ($index)) {
      $output = array ();
      foreach ($index as $key)
        $output[$key] = self::fetchFromArray ($array, $key, $xssClean);
      return $output;
    }

    if (isset ($array[$index])) {
      $value = $array[$index];
    } else if (($count = preg_match_all ('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) {
      $value = $array;
      for ($i = 0; $i < $count; $i++) {
        $key = trim ($matches[0][$i], '[]');
        if ($key === '') break;

        if (isset ($value[$key])) $value = $value[$key];
        else return null;
      }
    } else {
      return null;
    }

    return (($xssClean === null) ? config ('other', 'global_xss_filtering') : $xssClean) ? Security::xssClean ($value) : $value;
  }
  public static function get ($index = null, $xssClean = true) {
    return self::fetchFromArray ($_GET, $index, $xssClean);
  }
  public static function post ($index = null, $xssClean = null) {
    return self::fetchFromArray ($_POST, $index, $xssClean);
  }
  public static function postGet ($index, $xssClean = null) {
    return isset ($_POST[$index]) ? self::post ($index, $xssClean) : self::get ($index, $xssClean);
  }
  public static function getPost($index, $xssClean = null) {
    return isset ($_GET[$index]) ? self::get ($index, $xssClean) : self::post ($index, $xssClean);
  }
  public static function cookie ($index = null, $xssClean = null) {
    return self::fetchFromArray ($_COOKIE, $index, $xssClean);
  }
  public static function server ($index, $xssClean = null) {
    return self::fetchFromArray ($_SERVER, $index, $xssClean);
  }
  public static function userAgent ($xssClean = null) {
    return self::fetchFromArray ($_SERVER, 'HTTP_USER_AGENT', $xssClean);
  }
  public static function requestHeaders ($xssClean = true) {
    if (self::$headers !== null)
      return self::fetchFromArray (self::$headers, null, $xssClean);

    if (function_exists ('apache_request_headers')) {
      self::$headers = apache_request_headers ();
    } else {
      if (isset ($_SERVER['CONTENT_TYPE']))
        self::$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];

      foreach ($_SERVER as $key => $val)
        if (sscanf ($key, 'HTTP_%s', $header) === 1) {
          $header = str_replace ('_', ' ', strtolower ($header));
          $header = str_replace (' ', '-', ucwords ($header));

          self::$headers[$header] = $_SERVER[$key];
        }
    }

    return self::fetchFromArray (self::$headers, null, $xssClean);
  }
  public static function requestHeader ($index = null, $xssClean = true) {
    $headers = self::requestHeaders ($xssClean);
    if (!$index) return $headers;

    $headers = array_change_key_case ($headers, CASE_LOWER);
    $index = strtolower ($index);

    if (!isset ($headers[$index]))
      return null;

    return (($xssClean === null) ? config ('other', 'global_xss_filtering') : $xssClean) ? Security::xssClean ($headers[$index]) : $headers[$index];
  }
  public static function ip () {
    if (self::$ip !== null) return self::$ip;

    $proxy_ips = config ('other', 'proxy_ips');

    if ($proxy_ips && is_string ($proxy_ips))
      $proxy_ips = explode (',', str_replace (' ', '', $proxy_ips));

    self::$ip = self::server ('REMOTE_ADDR');

    if ($proxy_ips && is_array ($proxy_ips)) {
      foreach (array ('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP') as $header)
        if (($spoof = self::server ($header)) !== null) {
          sscanf ($spoof, '%[^,]', $spoof);
          if (!self::validIp ($spoof)) $spoof = null;
          else break;
        }

      if ($spoof) {
        for ($i = 0, $c = count($proxy_ips); $i < $c; $i++) {
          if (strpos($proxy_ips[$i], '/') === false) {
            if ($proxy_ips[$i] === self::$ip) {
              self::$ip = $spoof;
              break;
            }
            continue;
          }

          isset ($separator) || $separator = self::validIp (self::$ip, 'ipv6') ? ':' : '.';

          if (strpos ($proxy_ips[$i], $separator) === false)
            continue;

          if (!isset ($ip, $sprintf)) {
            if ($separator === ':') {
              $ip = explode (':', str_replace ('::', str_repeat (':', 9 - substr_count (self::$ip, ':')), self::$ip));
              
              for ($j = 0; $j < 8; $j++)
                $ip[$j] = intval ($ip[$j], 16);

              $sprintf = '%016b%016b%016b%016b%016b%016b%016b%016b';
            } else {
              $ip = explode ('.', self::$ip);
              $sprintf = '%08b%08b%08b%08b';
            }

            $ip = vsprintf ($sprintf, $ip);
          }

          sscanf ($proxy_ips[$i], '%[^/]/%d', $netaddr, $masklen);

          if ($separator === ':') {
            $netaddr = explode (':', str_replace ('::', str_repeat (':', 9 - substr_count ($netaddr, ':')), $netaddr));

            for ($j = 0; $j < 8; $j++)
              $netaddr[$j] = intval ($netaddr[$j], 16);
          } else {
            $netaddr = explode ('.', $netaddr);
          }

          if (strncmp ($ip, vsprintf ($sprintf, $netaddr), $masklen) === 0) {
            self::$ip = $spoof;
            break;
          }
        }
      }
    }

    if (!self::validIp (self::$ip))
      return self::$ip = '0.0.0.0';

    return self::$ip;
  }
  public static function validIp ($ip, $which = '') {
    switch (strtolower ($which)) {
      case 'ipv4':
        $which = FILTER_FLAG_IPV4;
        break;

      case 'ipv6':
        $which = FILTER_FLAG_IPV6;
        break;

      default:
        $which = null;
        break;
    }

    return (bool)filter_var ($ip, FILTER_VALIDATE_IP, $which);
  }
  public static function inputStream ($index = null, $xssClean = null) {
    if (self::$inputStream !== null)
      return self::fetchFromArray (self::$inputStream, $index, $xssClean);

    $raw_input_stream = file_get_contents ('php://input');
    parse_str ($raw_input_stream, self::$inputStream);
    is_array (self::$inputStream) || self::$inputStream = array ();
    
    return self::fetchFromArray (self::$inputStream, $index, $xssClean);
  }
  public static function setCookie ($name, $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = null, $httponly = null) {
    if (is_array ($name))
      foreach (array('value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly', 'name') as $item)
        if (isset($name[$item]))
          $$item = $name[$item];

    if ($prefix === '' && config ('cookie', 'prefix') !== '')
      $prefix = config ('cookie', 'prefix');

    if ($domain == '' && config ('cookie', 'domain') != '')
      $domain = config ('cookie', 'domain');

    if ($path === '/' && config ('cookie', 'path') !== '/')
      $path = config ('cookie', 'path');

    $secure = ($secure === null && config ('cookie', 'secure') !== null) ? (bool) config ('cookie', 'secure') : (bool) $secure;

    $httponly = ($httponly === null && config ('cookie', 'httponly') !== null) ? (bool) config ('cookie', 'httponly') : (bool) $httponly;

    $expire = !is_numeric ($expire)? time() - 86500 : (($expire > 0) ? time() + $expire : 0);

    setcookie ($prefix . $name, $value, $expire, $path, $domain, $secure, $httponly);
  }

  public static function transposedFilesArray ($files) {
    $filter_size = true;
    $new_array = array ();

    for ($i = $j = 0, $c = count ($files['name']), $keys = array_keys ($files); $i < $c; $i++)
      if ((!is_array ($files['size']) && (!$filter_size || $files['size']!=0)) || (!$filter_size || $files['size'][$i] !=0)) {
        foreach ($keys as $key)
          $new_array[$j][$key] = is_array ($files[$key]) ? $files[$key][$i] : $files[$key];
        $j++;
      }
    return $new_array;
  }
  public static function transposedAllFilesArray ($files_list) {
    $new_array = array ();
    if ($files_list)
      foreach ($files_list as $key => $files)
        $new_array[$key] = self::transposedFilesArray ($files);
      
    return $new_array;
  }

  public static function element ($item, $array, $default = false) {
    return !isset ($array[$item]) || ($array[$item] == "") ? $default : $array[$item];
  }
  public static function getUploadFile ($tag_name, $type = 'all') {
    $list = self::element ($tag_name, self::transposedAllFilesArray ($_FILES), array ());
    if ($type == 'one') if (count ($list)) return $list[0]; else return null;
    else if (count ($list)) return $list; else return array ();
  }
  public static function file ($index = null) {
    if (!$_FILES)
      return array ();

    if ($index === null)
      return array_filter (array_map (function ($t) {
        return is_array ($t) && count ($t) == 1 ? $t[0] : $t;
      }, self::transposedAllFilesArray ($_FILES)));
      // return array_filter (self::transposedAllFilesArray ($_FILES));

    // if (isset ($_FILES[$index]['name']) && count ($_FILES[$index]['name']) > 1)
    //   $index = $index . '[]';

    preg_match_all ('/^(?P<var>\w+)(\s?\[\s?\]\s?)$/', $index, $matches);

    return ($matches = $matches['var'] ? $matches['var'][0] : null) ? self::getUploadFile ($matches) : self::getUploadFile ($index, 'one');
  }
}
