<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('web_file_exists')) {
  function web_file_exists ($url, $cainfo = null) {
    $options = array (CURLOPT_URL => $url, CURLOPT_NOBODY => 1, CURLOPT_FAILONERROR => 1, CURLOPT_RETURNTRANSFER => 1);

    if (is_readable ($cainfo))
      $options[CURLOPT_CAINFO] = $cainfo;

    $ch = curl_init ($url);
    curl_setopt_array ($ch, $options);
    return curl_exec ($ch) !== false;
  }
}
if (!function_exists ('download_web_file')) {
  function download_web_file ($url, $fileName = null, $is_use_reffer = false, $cainfo = null) {
    if (!web_file_exists ($url, $cainfo))
      return null;

    if (is_readable ($cainfo))
      $url = str_replace (' ', '%20', $url);

    $options = array (
      CURLOPT_URL => $url, CURLOPT_TIMEOUT => 120, CURLOPT_HEADER => false, CURLOPT_MAXREDIRS => 10,
      CURLOPT_AUTOREFERER => true, CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36",
    );

    if (is_readable ($cainfo))
      $options[CURLOPT_CAINFO] = $cainfo;

    if ($is_use_reffer)
      $options[CURLOPT_REFERER] = $url;

    $ch = curl_init ($url);
    curl_setopt_array ($ch, $options);
    $data = curl_exec ($ch);
    curl_close ($ch);

    if (!$fileName)
      return $data;

    $write = fopen ($fileName, 'w');
    fwrite ($write, $data);
    fclose ($write);

    $oldmask = umask (0);
    @chmod ($fileName, 0777);
    umask ($oldmask);

    return filesize ($fileName) ?  $fileName : null;
  }
}
if (!function_exists ('force_download')) {
  function force_download ($filename = '', $data = '', $set_mime = false) {
    if ($filename === '' || $data === '')
      return;

    if ($data === null) {
      if (!@is_file ($filename) || ($filesize = @filesize ($filename)) === false)
        return;

      $filepath = $filename;
      $filename = explode ('/', str_replace (DIRECTORY_SEPARATOR, '/', $filename));
      $filename = end ($filename);
    } else {
      $filesize = strlen ($data);
    }

    $mime = 'application/octet-stream';

    $x = explode ('.', $filename);
    $extension = end ($x);

    if ($set_mime === true) {
      if (count ($x) === 1 || $extension === '')
        return;

      if ($t = config ('mimes', $extension))
        $mime = is_array ($t) ? $t[0] : $t;
    }

    if (count ($x) !== 1 && isset ($_SERVER['HTTP_USER_AGENT']) && preg_match ('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT'])) {
      $x[count($x) - 1] = strtoupper ($extension);
      $filename = implode ('.', $x);
    }

    if ($data === null && ($fp = @fopen ($filepath, 'rb')) === false)
      return;

    if (ob_get_level () !== 0 && @ob_end_clean () === false)
      @ob_clean ();

    header ('Content-Type: ' . $mime);
    header ('Content-Disposition: attachment; filename="' . $filename . '"');
    header ('Expires: 0');
    header ('Content-Transfer-Encoding: binary');
    header ('Content-Length: ' . $filesize);
    header ('Cache-Control: private, no-transform, no-store, must-revalidate');

    if ($data !== null)
      exit($data);

    while (!feof ($fp) && ($data = fread ($fp, 1048576)) !== false)
      echo $data;

    fclose ($fp);
    exit;
  }
}
