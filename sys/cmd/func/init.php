<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('headerText')) {
  function headerText () {
    system ('clear');
    echo cc ('╔' . str_repeat ('═', CLI_LEN - 2) . '╗', 'N') . "\n";
    echo cc ('║' . str_repeat (' ', CLI_LEN - 2) . '║', 'N') . "\n";
    echo cc ('║', 'N') . cc ('  歡迎使用 OACI 初始化工具', 'y') . cc (' v1.0', 'N') . str_repeat (' ', CLI_LEN - 33) . cc ('║', 'N') . "\n";
    echo cc ('║', 'N') . str_repeat (' ', CLI_LEN - 15) . '    ' . cc ('by', 'N') . ' ' . cc ('OA Wu ', 'W') . cc ('║', 'N') . "\n";
  }
}

if (!function_exists ('writeIndex')) {
  function writeIndex ($path) {
    return file_exists ($path .=  DIRECTORY_SEPARATOR . 'index.html') ? true : write_file ($path, "<!DOCTYPE html>\n" . "<html>\n" . "<head>\n" . "  <meta http-equiv=\"Content-type\" content=\"text/html; charset=utf-8\" />\n" . "  <title>403 禁止訪問</title>\n" . "</head>\n" . "<body>\n" . "\n" . "<p>您無權查看該網頁。</p>\n" . "\n" . "</body>\n" . "</html>");
  }
}

if (!function_exists ('myMkdir')) {
  function myMkdir ($path) {
    $oldmask = umask (0);
    $return = @mkdir ($path, 0777, true);
    umask ($oldmask);

    return $return ? writeIndex ($path) : false;
  }
}

if (!function_exists ('mkCmd')) {
  function mkCmd () {
    $link = 'cmd'; $target = 'sys/cmd';
    
    !file_exists (FCPATH . $link) || @unlink (FCPATH . $link);

    if (!(@symlink (FCPATH . $target, FCPATH . $link) && file_exists (FCPATH . $link)))
      return false;

    $oldmask = umask (0);
    @chmod ($link, 0777);
    umask ($oldmask);

    return true;
  }
}



if (!function_exists ('dbText')) {
  function dbText ($env = 0, $host = null, $acc = null, $psw = null, $table = null, $charset = null) {
    headerText ();

    echo cc ('╠══════╦' . str_repeat ('═', CLI_LEN - 9) . '╣', 'N') . "\n" . cc ('║', 'N') . ' 選項 ' . cc ('║', 'N') . ' 開發環境 ' . str_repeat (' ', CLI_LEN - 19) . cc ('║', 'N') . "\n" . cc ('╟──────╫' . str_repeat ('─', CLI_LEN - 9) . '╢', 'N') . "\n" . cc ('║', 'N') . ' ' . cc ($env == '1' ? '➜' : ' ', 'y') . cc (' 1. ', $env == '1' ? 'Y' : null) . cc ('║', 'N') . cc (' 開發環境 ', $env == '1' ? 'Y' : null) . cc ('(development)', $env == '1' ? 'y' : 'N') . str_repeat (' ', CLI_LEN - 32) . cc ('║', 'N') . "\n" . cc ('║', 'N') . ' ' . cc ($env == '2' ? '➜' : ' ', 'y') . cc (' 2. ', $env == '2' ? 'Y' : null) . cc ('║', 'N') . cc (' 正式環境 ', $env == '2' ? 'Y' : null) . cc ('(production)', $env == '2' ? 'y' : 'N') . str_repeat (' ', CLI_LEN - 31) . cc ('║', 'N') . "\n";

    if (!$env) return;
    echo ($host ? cc ('╠══════╩' . str_repeat ('═', CLI_LEN - 9) . '╣', 'N') . "\n" : cc ('╚══════╩' . str_repeat ('═', CLI_LEN - 9) . '╝', 'N') . "\n" . cc ('╔' . str_repeat ('═', CLI_LEN - 2) . '╗', 'N') . "\n") . cc ('║', 'N') . " 設定資料庫 " . str_repeat (' ', CLI_LEN - 14) . cc('║', 'N') . "\n";

    if (!$host) return print (cc ('╚' . str_repeat ('═', CLI_LEN - 2) . '╝', 'N') . "\n");
    echo cc ('╠' . str_repeat ('═', 16) . '╦' . str_repeat ('═', CLI_LEN - 2 - 16 - 1) . '╣', 'N') . "\n" . ($host ? cc ('║', 'N') . " 位址" . cc ('(hostname) ║', 'N') . ' ' . sprintf ('%-' . (CLI_LEN - 20) . 's', $host) . cc('║', 'N') . "\n" : '');
    
    if ($host && !$acc) return print (cc ('╚' . str_repeat ('═', 16) . '╩' . str_repeat ('═', CLI_LEN - 2 - 16 - 1) . '╝', 'N') . "\n");
    echo cc ('╟' . str_repeat ('─', 16) . '╫' . str_repeat ('─', CLI_LEN - 2 - 16 - 1) . '╢', 'N') . "\n" . ($acc ? cc ('║', 'N') . " 帳號" . cc ('(username) ║', 'N') . ' ' . sprintf ('%-' . (CLI_LEN - 20) . 's', $acc) . cc('║', 'N') . "\n" : '');
    
    if ($host && $acc && !$psw) return print (cc ('╚' . str_repeat ('═', 16) . '╩' . str_repeat ('═', CLI_LEN - 2 - 16 - 1) . '╝', 'N') . "\n");
    echo cc ('╟' . str_repeat ('─', 16) . '╫' . str_repeat ('─', CLI_LEN - 2 - 16 - 1) . '╢', 'N') . "\n" . ($psw ? cc ('║', 'N') . " 密碼" . cc ('(password) ║', 'N') . ' ' . sprintf ('%-' . (CLI_LEN - 20) . 's', $psw) . cc('║', 'N') . "\n" : '');

    if ($host && $acc && $psw && !$table) return print (cc ('╚' . str_repeat ('═', 16) . '╩' . str_repeat ('═', CLI_LEN - 2 - 16 - 1) . '╝', 'N') . "\n");
    echo cc ('╟' . str_repeat ('─', 16) . '╫' . str_repeat ('─', CLI_LEN - 2 - 16 - 1) . '╢', 'N') . "\n" . ($table ? cc ('║', 'N') . " 名稱" . cc ('(database) ║', 'N') . ' ' . sprintf ('%-' . (CLI_LEN - 20) . 's', $table) . cc('║', 'N') . "\n" : '');
    
    if ($host && $acc && $psw && $table && !$charset) return print (cc ('╚' . str_repeat ('═', 16) . '╩' . str_repeat ('═', CLI_LEN - 2 - 16 - 1) . '╝', 'N') . "\n");
    echo cc ('╟' . str_repeat ('─', 16) . '╫' . str_repeat ('─', CLI_LEN - 2 - 16 - 1) . '╢', 'N') . "\n" . ($charset ? cc ('║', 'N') . " 編碼" . cc ('(char set) ║', 'N') . ' ' . sprintf ('%-' . (CLI_LEN - 20) . 's', $charset) . cc('║', 'N') . "\n" : '');
    
    if ($host && $acc && $psw && $table && $charset) return print (cc ('╚' . str_repeat ('═', 16) . '╩' . str_repeat ('═', CLI_LEN - 2 - 16 - 1) . '╝', 'N') . "\n");
  }
}

if (!function_exists ('env')) {
  function env () {
    do {
      dbText ();
      echo cc ('╟ ─ ─ ─║─' . str_repeat (' ─', (CLI_LEN - 9) / 2) . '║', 'N') . "\n" . cc ('║', 'N') . cc ('   q. ', 'W') . cc ('║', 'N') . ' 沒事，按錯.. 離開本程式 ' . str_repeat (' ', CLI_LEN - 23 - 11) . cc ('║', 'N') . "\n" . cc ('╚══════╩' . str_repeat ('═', CLI_LEN - 9) . '╝', 'N') . "\n\n " . cc ('➜', 'G') . ' 請輸入您的選項' . cc ('(q)', 'N') . '：';
      ($env = trim (fgets (STDIN))) || $env = 'q';
    } while (!in_array (strtolower ($env), array ('1', '2', 'q')));

    $env == 'q' && exit ("\n" . cc (str_repeat ('═', CLI_LEN), 'N') . "\n\n  好的！下次別再按錯囉，期待您下次再使用，" . cc ('掰掰', 'W') . "～  \n\n" . cc (str_repeat ('═', CLI_LEN), 'N') . "\n\n");

    return $env;
  }
}

if (!function_exists ('host')) {
  function host ($env) {
    do {
      dbText ($env);
      echo "\n " . cc ('➜', 'G') . ' 請輸入主機' . cc ('(127.0.0.1)', 'N') . '：';
      ($host = trim (fgets (STDIN))) || $host = '127.0.0.1';
    } while (!$host);

    return $host;
  }
}

if (!function_exists ('acc')) {
  function acc ($env, $host) {
    do {
      dbText ($env, $host);
      echo "\n " . cc ('➜', 'G') . ' 請輸入帳號' . cc ('(root)', 'N') . '：';
      ($acc = trim (fgets (STDIN))) || $acc = 'root';
    } while (!$acc);
    
    return $acc;
  }
}

if (!function_exists ('psw')) {
  function psw ($env, $host, $acc) {
    do {
      dbText ($env, $host, $acc);
      echo "\n " . cc ('➜', 'G') . ' 請輸入密碼' . '：';
    } while (!$psw = trim (fgets (STDIN)));

    return $psw;
  }
}

if (!function_exists ('table')) {
  function table ($env, $host, $acc, $psw) {
    do {
      dbText ($env, $host, $acc, $psw);
      echo "\n " . cc ('➜', 'G') . ' 請輸入資料庫名稱' . '：';
    } while (!$table = trim (fgets (STDIN)));

    return $table;
  }
}

if (!function_exists ('charset')) {
  function charset ($env, $host, $acc, $psw, $table) {
    do {
      dbText ($env, $host, $acc, $psw, $table);
      echo "\n " . cc ('➜', 'G') . ' 請輸入編碼方式' . cc ('(utf8)', 'N') . '：';
      ($charset = trim (fgets (STDIN))) || $charset = 'utf8';
    } while (!$charset);

    return $charset;
  }
}
