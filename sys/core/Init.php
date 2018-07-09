<?php defined ('OACI') || exit ('此檔案不允許讀取。');

if (!function_exists ('request_is_cli')) {
  function request_is_cli () {
    return PHP_SAPI === 'cli' || defined ('STDIN');
  }
}

if (!function_exists ('is_php')) {
  function is_php ($version) {
    static $_is_php;
    return !isset ($_is_php[$version = (string)$version]) ? $_is_php[$version] = version_compare (PHP_VERSION, $version, '>=') : $_is_php[$version];
  }
}

if (!function_exists ('cc')) {
  function cc ($str, $fc = null, $bc = null) {
    if (!strlen ($str)) return "";
    $nstr = ""; $keys = array ('n' => '30', 'w' => '37', 'b' => '34', 'g' => '32', 'c' => '36', 'r' => '31', 'p' => '35', 'y' => '33');
    if ($fc && in_array (strtolower ($fc), array_map ('strtolower', array_keys ($keys)))) { $fc = !in_array (ord ($fc[0]), array_map ('ord', array_keys ($keys))) ? in_array (ord ($fc[0]) | 0x20, array_map ('ord', array_keys ($keys))) ? '1;' . $keys[strtolower ($fc[0])] : null : $keys[$fc[0]]; $nstr .= $fc ? "\033[" . $fc . "m" : ""; }
    $nstr .= $bc && in_array (strtolower ($bc), array_map ('strtolower', array_keys ($keys))) ? "\033[" . ($keys[strtolower ($bc[0])] + 10) . "m" : "";
    if (substr ($str, -1) == "\n") { $str = substr ($str, 0, -1); $has_new_line = true; } else { $has_new_line = false; }
    $nstr .=  $str . "\033[0m"; $nstr = $nstr . ($has_new_line ? "\n" : "");
    return $nstr;
  }
}

if (!function_exists ('statuses')) {
  function statuses ($code) {
    // https://zh.wikipedia.org/wiki/HTTP%E7%8A%B6%E6%80%81%E7%A0%81
    $statuses = array (100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => 'Switch Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot', 420 => 'Enhance Your Caim', 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Unodered Cellection', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 444 => 'No Response', 450 => 'Blocked by Windows Parental Controls', 451 => 'Unavailable For Legal Reasons', 494 => 'Request Header Too Large', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 510 => 'Not Extended', 511 => 'Network Authentication Required');

    return isset ($statuses[$code]) ? $statuses[$code] : null;
  }
}
if (!function_exists ('setStatusHeader')) {
  function setStatusHeader ($code, $str = '') {
      $str = statuses ($code);
      $str || (($code = 500) && ($str = statuses ($code)));

      if (strpos (PHP_SAPI, 'cgi') === 0)
        return header ('Status: ' . $code . ' ' . $str, true);

      in_array (($protocol = isset ($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1'), array ('HTTP/1.0', 'HTTP/1.1', 'HTTP/2')) || $protocol = 'HTTP/1.1';
      return header ($protocol . ' ' . $code . ' ' . $str, true, $code);
  }
}

if (!function_exists ('gg')) {
  function gg ($str, $code = 500, $contents = array ()) {//$title, $h1 = array (), $contents = array (), $header = array ()
    $text = ($text = statuses ($code)) ? $code . ' ' . $text : '';

    if (isset ($contents['quote']))
      $contents['quote'] = $str;
    else
      $contents = array_merge (array ('quote' => $str), $contents);

    isset ($contents['traces']) || $contents['traces'] = array_combine (array_map (function ($trace) { return (isset ($trace['file']) ? str_replace ('', '', $trace['file']) : '[呼叫函式]') . (isset ($trace['line']) ? '(' . $trace['line'] . ')' : ''); }, $traces = debug_backtrace (DEBUG_BACKTRACE_PROVIDE_OBJECT)), array_map (function ($trace) { return (isset ($trace['class']) ? $trace['class'] : '') . (isset ($trace['type']) ? $trace['type'] : '') . (isset ($trace['function']) ? $trace['function'] : '') . (isset ($trace['args']) ? '(' . implode_recursive (', ', $trace['args']) . ')' : ''); }, $traces = debug_backtrace (DEBUG_BACKTRACE_PROVIDE_OBJECT)));

    if (request_is_cli ()) {
      @system ('clear');
      echo "\n" . cc (str_repeat ('═', CLI_LEN), 'N') . "\n\n";
      echo cc (' 錯誤', 'r') . cc (' :: ', 'N') . cc ($text, 'W') . "\n";
      echo "\n" . cc (str_repeat ('═', CLI_LEN), 'N') . "\n\n";
      echo implode ("\n", array_filter (array_map (function ($content, $key) { return  $key != 'quote' ? $key != 'detail' ? $key == 'traces' ? implode ("\n", array_map (function ($val, $key) { return cc (' ◎ ', 'G') . $key . cc (' ➜ ', 'N') . $val; }, $content, array_keys ($content))) . "\n" : '' : implode ("\n", array_map (function ($val, $key) { return cc (' ◎ ', 'G') . $key . cc ('：', 'N') . $val; }, $content, array_keys ($content))) . "\n" : cc (' ◎ ', 'G') . cc ('“', 'N') . $content . cc ('”', 'N') . "\n"; }, $contents, array_keys ($contents))));
      echo "\n";
    } else {
      @ob_end_clean ();
      setStatusHeader ($code, $str);
      echo '<!DOCTYPE html><html lang="tw"><head><meta http-equiv="Content-Language" content="zh-tw" /><meta http-equiv="Content-type" content="text/html; charset=utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui" />';
      echo '<title>' . '錯誤' . ($text ? ' :: ' . $text : '') . '</title><style type="text/css">*,*:after,*:before{vertical-align:top;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;box-sizing:border-box;-moz-osx-font-smoothing:antialiased;-webkit-font-smoothing:antialiased;-moz-font-smoothing:antialiased;-ms-font-smoothing:antialiased;-o-font-smoothing:antialiased}*::-moz-selection,*:after::-moz-selection,*:before::-moz-selection{color:#fff;background-color:#96c8ff}*::selection,*:after::selection,*:before::selection{color:#fff;background-color:#96c8ff}html{min-height:100%}html body{position:relative;display:inline-block;width:100%;min-height:100%;margin:0;padding:0;color:#5a5a5a;text-align:center;font-size:medium;font-family:Roboto, RobotoDraft, Helvetica, Arial, sans-serif, "微軟正黑體", "Microsoft JhengHei";background:#f0f0f0;-moz-osx-font-smoothing:antialiased;-webkit-font-smoothing:antialiased;-moz-font-smoothing:antialiased;-ms-font-smoothing:antialiased;-o-font-smoothing:antialiased}a{display:inline;font-weight:normal;text-decoration:none;-moz-transition:color .3s,border-bottom .3s;-o-transition:color .3s,border-bottom .3s;-webkit-transition:color .3s,border-bottom .3s;transition:color .3s,border-bottom .3s;color:#4285f4;border-bottom:1px solid #4285f4;border-bottom-color:rgba(70,136,241,0.4);border-bottom-style:dashed;-moz-transition:border-bottom-color .3s,color .3s;-o-transition:border-bottom-color .3s,color .3s;-webkit-transition:border-bottom-color .3s,color .3s;transition:border-bottom-color .3s,color .3s}a.active,a:hover{color:#0d5bdd;border-bottom:1px solid #0d5bdd}a:hover{border-bottom-color:rgba(70,136,241,0.8);border-bottom-style:dashed}#main{display:inline-block;width:100%}#main>div{display:inline-block;width:100%;max-width:960px;background-color:white;padding:20px 32px;text-align:left;-moz-border-radius:2px;-webkit-border-radius:2px;border-radius:2px;-moz-box-shadow:0 0 1px #c8c8c8,1px 1px 1px #c8c8c8;-webkit-box-shadow:0 0 1px #c8c8c8,1px 1px 1px #c8c8c8;box-shadow:0 0 1px #c8c8c8,1px 1px 1px #c8c8c8;margin-top:16px;margin-bottom:16px}@media screen and (max-width: 959px) and (min-width: 0){#main>div{margin-bottom:0;margin-top:0}}@media screen and (max-width: 749px) and (min-width: 0){#main>div{padding:20px}}@media screen and (max-width: 499px) and (min-width: 0){#main>div{padding:12px}}#main>div>h1,#main>div>h2,#main>div>h3{position:relative;font-size:20px;display:inline-block;width:100%;height:32px;line-height:32px;margin:0;margin-top:4px}#main>div>h1:not(:first-child),#main>div>h2:not(:first-child),#main>div>h3:not(:first-child){margin-top:32px}#main>div>h1:before,#main>div>h2:before,#main>div>h3:before{position:absolute;left:0;top:0;display:none;width:32px;line-height:32px;height:32px;text-align:center;color:#969682;font-weight:bold}#main>div>h1:after,#main>div>h2:after,#main>div>h3:after{display:none;width:100%;height:14px;line-height:14px;font-size:13px;font-weight:normal;color:#828282}#main>div>h1[data-icon]:not([data-icon=""]),#main>div>h2[data-icon]:not([data-icon=""]),#main>div>h3[data-icon]:not([data-icon=""]){padding-left:48px}#main>div>h1[data-icon]:not([data-icon=""]):before,#main>div>h2[data-icon]:not([data-icon=""]):before,#main>div>h3[data-icon]:not([data-icon=""]):before{display:inline-block;content:attr(data-icon);-moz-transform:rotate(0);-ms-transform:rotate(0);-webkit-transform:rotate(0);transform:rotate(0);font-size:32px;line-height:30px}#main>div>h1[data-icon]:not([data-icon=""])[data-msg]:not([data-msg=""]),#main>div>h2[data-icon]:not([data-icon=""])[data-msg]:not([data-msg=""]),#main>div>h3[data-icon]:not([data-icon=""])[data-msg]:not([data-msg=""]){padding-left:64px}#main>div>h1[data-msg]:not([data-msg=""]),#main>div>h2[data-msg]:not([data-msg=""]),#main>div>h3[data-msg]:not([data-msg=""]){height:48px}#main>div>h1[data-msg]:not([data-msg=""]):before,#main>div>h2[data-msg]:not([data-msg=""]):before,#main>div>h3[data-msg]:not([data-msg=""]):before{width:48px;line-height:50px;height:48px;font-size:48px}#main>div>h1[data-msg]:not([data-msg=""]):after,#main>div>h2[data-msg]:not([data-msg=""]):after,#main>div>h3[data-msg]:not([data-msg=""]):after{display:inline-block;content:attr(data-msg)}#main>div>h2{font-size:18px}#main>div>h3{font-size:16px}#main>div>i{display:block;width:calc(100% + 32px * 2);margin-left:-32px;height:1px;margin-top:20px;margin-bottom:32px;border-top:1px solid #e6e6e6}@media screen and (max-width: 749px) and (min-width: 0){#main>div>i{width:calc(100% + 10px * 2);margin-left:-10px}}@media screen and (max-width: 499px) and (min-width: 0){#main>div>i{width:calc(100% + 6px * 2);margin-left:-6px}}#main>div>i+*{margin-top:0 !important}#main>div>i+i{display:none}#main>div>blockquote{display:inline-block;width:100%;margin:0;padding:8px 16px;border-left:3px solid #c8c8c8;margin-top:32px;color:#787878}#main>div>blockquote+table{margin-top:20px}#main>div>blockquote+blockquote{margin-top:16px}#main>div>table{margin:0;margin-top:12px;width:100%;border-spacing:0;border-collapse:separate;color:#2e2f30;font-size:14px}#main>div>table td,#main>div>table th{word-break:break-all;word-break:break-word;line-height:20px}#main>div>table td.c,#main>div>table th.c{text-align:center}#main>div>table td.l,#main>div>table th.l{text-align:left}#main>div>table td.r,#main>div>table th.r{text-align:right}#main>div>table thead tr{height:32px}#main>div>table thead tr th{padding:6px;background-color:#e8e8e8;border-left:1px solid #dedede}#main>div>table thead tr th:last-child{border-right:1px solid #dedede}#main>div>table thead tr:first-child th{border-top:1px solid #dedede}#main>div>table thead+tbody tr:nth-child(2n+1){background-color:#fff}#main>div>table thead+tbody tr:nth-child(2n){background-color:#f7f7f7}#main>div>table thead+tbody:nth-child(2n+1){background-color:#f7f7f7}#main>div>table thead+tbody:nth-child(2n){background-color:#fff}#main>div>table tbody tr{min-height:32px;background-color:#fff}#main>div>table tbody tr td,#main>div>table tbody tr th{padding:6px;border-top:1px solid #dedede;border-left:1px solid #dedede;vertical-align:middle}#main>div>table tbody tr td:last-child,#main>div>table tbody tr th:last-child{border-right:1px solid #dedede}#main>div>table tbody tr td>i,#main>div>table tbody tr th>i{filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=75);opacity:.75;font-size:13px}#main>div>table tbody tr td .p,#main>div>table tbody tr th .p{display:inline-block;position:relative;font-size:0;white-space:nowrap;width:20px;margin-right:2px;overflow:hidden;-moz-transition:width .3s;-o-transition:width .3s;-webkit-transition:width .3s;transition:width .3s;color:#646464}#main>div>table tbody tr td .p.s,#main>div>table tbody tr th .p.s{width:auto;font-size:14px}#main>div>table tbody tr td .p.s:after,#main>div>table tbody tr th .p.s:after{content:"";background-color:rgba(255,255,255,0)}#main>div>table tbody tr td .p:after,#main>div>table tbody tr th .p:after{content:".../";position:absolute;left:0;top:0;display:inline-block;width:100%;height:100%;font-size:14px;text-align:center;cursor:pointer;-moz-transition:background-color .3s;-o-transition:background-color .3s;-webkit-transition:background-color .3s;transition:background-color .3s}#main>div>table tbody tr th{background-color:#f7f7f7;text-align:right}#main>div>table tbody tr:last-child td,#main>div>table tbody tr:last-child th{border-bottom:1px solid #dedede}#main>div>div{text-align:right;color:#787878;font-size:12px;display:inline-block;width:100%;height:20px;line-height:20px}#main>div>div a{font-size:11px}</style><script type="text/javascript">function init () { document.querySelectorAll ("a.p").forEach (function () { this.onclick = function (e) { e.srcElement.classList.toggle ("s") }; }); }</script></head><body lang="zh-tw" onload="init ();"><main id="main"><div>';
      echo '<h1 data-icon="⚠" data-msg="' . ($text ? $text : '') . '">' . '錯誤' . '</h1><i></i>';
      echo implode ('', array_filter (array_map (function ($content, $key) { return $key != 'quote' ? $key != 'detail' ? $key == 'traces' ? '<h2>回朔追蹤</h2><table><thead><tr><th width="50" class="c">順序</th><th width="250">標題</th><th>內容</th></tr></thead>' . implode ('', array_map (function ($val, $key, $i) { $dir = pathinfo ($key, PATHINFO_DIRNAME); $base = pathinfo ($key, PATHINFO_BASENAME); return '<tr><td class="c"><i>#' . $i . '</i></td><td>' . ($dir && $dir != '.'  ? '<a class="p">' . $dir . DIRECTORY_SEPARATOR . '</a>' : '') . $base . '</td><td>' . $val . '</td></tr>'; }, $content, $ks = array_keys ($content), range (count ($ks), 1))) . '</tbody></table>' : '' : '<table><tbody>' . implode ('', array_map (function ($val, $key) { return '<tr><th width="100">' . $key . '</th><td>' . $val . '</td></tr>'; }, $content, array_keys ($content))) . '</tbody></table>' : '<blockquote>' . $content . '</blockquote>'; }, $contents, array_keys ($contents))));
      echo '<i></i><div>©2014 - ' . date ('Y') . ' <a href="https://www.ioa.tw/" target="_blank">OA Wu</a>, All Rights Reserved.</div></div></main></body></html>';
    }
    exit;
  }
}

if (!function_exists ('show404')) {
  function show404 () {
    gg ('找不到您的網頁耶，你是不是迷路了啊？', 404, !request_is_cli () && class_exists ('URL') ? array ('detail' => array ('建議' => '回首頁', '網址' => "<a href='" . URL::base () . "' target='_self'>" . URL::base () . "</a>")) : array ('detail' => array ('建議' => '回首頁')));
  }
}

if (!function_exists ('implode_recursive')) {
  function implode_recursive ($glue, $pieces) {
    $ret = '';

    foreach ($pieces as $piec)
      $ret .= isset ($piec) ? !is_object ($piec) ? !is_bool ($piec) ? is_array ($piec) ? 'array (' . implode_recursive ($glue, $piec) .')' . $glue : $piec . $glue : ($piec ? 'true' : 'false') . $glue : get_class ($piec) . $glue : 'null' . $glue;

    $ret = substr ($ret, 0, 0 - strlen ($glue));

    return $ret;
  }
}

if (!function_exists ('_error_handler')) {
  function _error_handler ($severity, $message, $filepath, $line) {
    $levels = array (E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parsing Error', E_NOTICE => 'Notice', E_CORE_ERROR => 'Core Error', E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning', E_USER_ERROR => 'User Error', E_USER_WARNING => 'User Warning', E_USER_NOTICE => 'User Notice', E_STRICT => 'Runtime Notice');
    $isError = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

//    $isError && setStatusHeader (500);

    if (($severity & error_reporting ()) !== $severity)
      return;

    if (str_ireplace (array ('off', 'none', 'no', 'false', 'null'), '', ini_get ('display_errors')))
      gg ('程式錯誤', 500, array (
        'detail' => array (
        '類型' => isset ($levels[$severity]) ? $levels[$severity] : $severity,
        '訊息' => $message,
        '位置' => $filepath . '(' . $line . ')'
        )));

    if ($isError)
      exit(1);
  }
}

if (!function_exists ('_exception_handler')) {
  function _exception_handler ($exception) {
    request_is_cli () || setStatusHeader (500);

    if (str_ireplace (array ('off', 'none', 'no', 'false', 'null'), '', ini_get ('display_errors')))
      gg ('有 Exception 未使用 try catch', 500, array (
        'detail' => array (
          '物件' => get_class ($exception),
          '訊息' => $exception->getMessage (),
          '檔案' => $exception->getFile () . '(' . $exception->getLine () . ')'),
        'traces' => array_combine (array_map (function ($trace) { return (isset ($trace['file']) ? str_replace ('', '', $trace['file']) : '[呼叫函式]') . (isset ($trace['line']) ? '(' . $trace['line'] . ')' : ''); }, $exception->getTrace ()), array_map (function ($trace) { return (isset ($trace['class']) ? $trace['class'] : '') . (isset ($trace['type']) ? $trace['type'] : '') . (isset ($trace['function']) ? $trace['function'] : '') . (isset ($trace['args']) ? '(' . implode_recursive (', ', $trace['args']) . ')' : ''); }, $exception->getTrace ())))
      );

    exit(1);
  }
}

if (!function_exists ('_shutdown_handler')) {
  function _shutdown_handler () {
    $lastError = error_get_last ();
    
    if (isset ($lastError) && ($lastError['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
      _error_handler ($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
  }
}

/* ------------------------------------------------------
 *  定義自己的 Error Handler
 * ------------------------------------------------------ */
set_error_handler ('_error_handler');
set_exception_handler ('_exception_handler');
register_shutdown_function ('_shutdown_handler');
