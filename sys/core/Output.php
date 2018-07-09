<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Output {
  private static $output;
  private static $zlibOc;
  private static $compressOutput;
  private static $cacheExpiration;
  private static $cacheMimeType;
  private static $headers;
  private static $mimes;

  public static function init () {
    self::$cacheExpiration = 0;
    self::$compressOutput = false && self::$zlibOc === false && extension_loaded ('zlib');
    self::$cacheMimeType = 'text/html';

    self::setHeader (array ());
    self::setOutput ('');

    self::$zlibOc = (bool)ini_get ('zlib.output_compression');
    self::$mimes = config ('mimes');

    if (self::displayCache () === true)
      exit;
  }

  public static function appendHeader ($header, $replace = true) {
    if (self::$zlibOc && strncasecmp ($header, 'content-length', 14) === 0)
      return $this;

    array_push (self::$headers, array ($header, $replace));
    return self::$headers;
  }

  public static function setHeader ($headers) {
    return self::$headers = $headers;
  }

  public static function getHeaders () {
    return self::$headers;
  }

  public static function appendOutput ($output) {
    return self::$output .= $output;
  }

  public static function setOutput ($output) {
    return self::$output = $output;
  }

  public static function getOutput () {
    return self::$output;
  }

  public static function getHeader ($header = null) {
    $headers = array_merge (array_map ('array_shift', self::getHeaders ()), headers_list ());

    if (!($headers && $header))
      return null;

    for ($c = count ($headers) - 1; $c > -1; $c--)
      if (strncasecmp ($header, $headers[$c], $l = Charset::strlen ($header)) === 0)
        return trim (Charset::substr ($headers[$c], $l+1));

    return null;
  }

  public static function getContentType () {
    for ($i = 0, $c = count ($this->headers); $i < $c; $i++)
      if (sscanf ($this->headers[$i][0], 'Content-Type: %[^;]', $contentType) === 1)
        return $contentType;

    return 'text/html';
  }

  public static function setContentType ($mimeType, $charset = null) {
    if (strpos ($mimeType, '/') === false) {
      $extension = ltrim ($mimeType, '.');

      if (isset (self::$mimes[$extension])) {
        $mimeType =& self::$mimes[$extension];
        is_array ($mimeType) && $mimeType = current ($mimeType);
      }
    }

    self::$cacheMimeType = $mimeType;
    $charset || $charset = config ('other', 'charset');

    $header = 'Content-Type: ' . $mimeType . (empty ($charset) ? '' : '; charset=' . $charset);

    self::appendHeader ($header, true);
  }

  public static function cache ($cacheExpiration) { // sec
    self::$cacheExpiration = is_numeric ($cacheExpiration) ? $cacheExpiration : 0;
  }

  public static function display ($output = '') {
    $output || $output =& self::$output;

    self::$cacheExpiration > 0 && self::writeCache ($output);
    self::$compressOutput === true && isset ($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos ($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && ob_start ('ob_gzhandler');

    foreach (self::getHeaders () as $header)
      @header ($header[0], $header[1]);

    echo $output;
  }

  private static function filename () {
    $uri = config ('other', 'base_url') . URL::uriString ();

    if (config ('cache', 'output', 'query_string'))
      $uri .= ($_SERVER['QUERY_STRING'] ? '?'. $_SERVER['QUERY_STRING'] : '');

    return md5 ($uri);
  }

  public static function writeCache ($output) {
    $path = config ('cache', 'output', 'path');

    if (!(is_dir ($path) && is_really_writable ($path)))
      return false;

    $path .= self::filename ();

    if (!(($fp = @fopen ($path, FOPEN_READ_WRITE_CREATE)) && flock ($fp, LOCK_EX)))
      return false;

    if (self::$compressOutput === true) {
      $output = gzencode ($output);
      self::getHeader ('content-type') === null && self::setContentType (self::$cacheMimeType);
    }

    $expire = time () + self::$cacheExpiration;

    $info = serialize (array ('expire' => $expire, 'headers' => self::getHeaders ()));

    $output = $info . 'ENDCI--->' . $output;

    for ($written = 0, $length = Charset::strlen ($output); $written < $length; $written += $result)
      if (($result = fwrite ($fp, Charset::substr ($output, $written))) === false)
        break;

    flock ($fp, LOCK_UN);
    fclose ($fp);

    if (!is_int ($result))
      return @unlink ($cache_path);

    chmod ($path, 0640);

    self::setCacheHeader ($_SERVER['REQUEST_TIME'], $expire);
  }

  public static function setCacheHeader ($last_modified, $expiration) {
    $max_age = $expiration - $_SERVER['REQUEST_TIME'];

    if (isset ($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $last_modified <= strtotime ($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      setStatusHeader (304);
      exit;
    }

    header ('Pragma: public');
    header ('Cache-Control: max-age=' . $max_age . ', public');
    header ('Expires: ' . gmdate ('D, d M Y H:i:s', $expiration) . ' GMT');
    header ('Last-modified: ' . gmdate ('D, d M Y H:i:s', $last_modified) . ' GMT');
  }
  
  public static function displayCache () {
    $path = config ('cache', 'output', 'path');

    $filepath = $path . self::filename ();

    if (!(file_exists ($filepath) && ($fp = @fopen ($filepath, FOPEN_READ))))
      return false;

    flock ($fp, LOCK_SH);

    $cache = (filesize ($filepath) > 0) ? fread ($fp, filesize ($filepath)) : '';

    flock ($fp, LOCK_UN);
    fclose ($fp);

    if (!preg_match ('/^(.*)ENDCI--->/', $cache, $match))
      return false;

    $info = unserialize ($match[1]);
    $expire = $info['expire'];

    $last_modified = filemtime ($filepath);

    if ($_SERVER['REQUEST_TIME'] >= $expire && is_really_writable ($path)) {
      @unlink ($filepath);
      return false;
    }

    self::setCacheHeader ($last_modified, $expire);

    foreach ($info['headers'] as $header)
      self::appendHeader ($header[0], $header[1]);

    self::display (Charset::substr ($cache, Charset::strlen ($match[0])));
    return true;
  }

  public static function deleteCache ($key) {
    $path = config ('cache', 'output', 'path');

    if (!is_dir ($path))
      return false;

    $path .= $key ? $key : self::filename ();

    return @unlink ($path);
  }

  public static function json ($arr = array (), $code = 200) {
    setStatusHeader ($code);
    
    if ($code != 200) {
      $str = statuses ($code);
      $str || $str = statuses (400);
      $arr = is_array ($arr) ? array_merge (array ('message' => $str), $arr) : array ('message' => is_string ($arr) ? $arr : $str);
    }

    Output::setContentType ('application/json');
    return Output::setOutput (is_array ($arr) ? json_encode ($arr) : $arr);
  }

  public static function html ($html = '') {
    Output::setContentType ('text/html');
    return Output::setOutput ($html);
  }
}
