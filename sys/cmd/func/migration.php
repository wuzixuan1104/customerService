<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('headerText')) {
  function headerText ($cho = 0) {
    system ('clear');
    $now = MigrationTool::nowVersion ();
    $files = MigrationTool::files (true);
    
    echo cc ('╔' . str_repeat ('═', CLI_LEN - 2) . '╗', 'N') . "\n";
    echo cc ('║' . str_repeat (' ', CLI_LEN - 2) . '║', 'N') . "\n";
    echo cc ('║', 'N') . cc ('  歡迎使用 OACI Migration 工具', 'y') . cc (' v1.0', 'N') . str_repeat (' ', CLI_LEN - 37) . cc ('║', 'N') . "\n";
    echo cc ('║', 'N') . str_repeat (' ', CLI_LEN - 15) . '    ' . cc ('by', 'N') . ' ' . cc ('OA Wu ', 'W') . cc ('║', 'N') . "\n";
    echo cc ('╠═══════╦' . str_repeat ('═', CLI_LEN - 12 - 20) . '╦' . str_repeat ('═', 21) . '╣', 'N') . "\n";
    echo cc ('║', 'N') . '  版本 ' . cc ('║', 'N') . ' 名稱 ' . str_repeat (' ', CLI_LEN - 12 - 25 - 1) . cc ('║', 'N') .  ' 新增日期' . str_repeat (' ', 21 - 9) . cc ('║', 'N') . " \n";
    echo cc ('╟───────╫' . str_repeat ('─', CLI_LEN - 12 - 20) . '╫' . str_repeat ('─', 21) . '╢', 'N') . "\n";

    foreach ($files as $v => $f) {
      $at = MigrationTool::get ($f);
      $at = $at['at'];
      $f = preg_replace ('/^\d{3}_/', '', basename ($f, '.php'));
      echo $now == $v ? cc ('║', 'N') . ' ' . cc ('➜', 'y') . ' ' . cc (sprintf ('%3s', $v), 'Y') . ' ' . cc ('║', 'N') . ' ' . cc (sprintf ('%-47s', $f), 'Y') . cc ('║', 'N') . ' ' . cc ($at, 'Y') . ' ' . cc ('║', 'N') . "\n" : cc ('║', 'N') . ' ' . '  ' . sprintf ('%3s', $v) . ' ' . cc ('║', 'N') . ' ' . sprintf ('%-47s', $f) . cc ('║', 'N') . ' ' . $at . ' ' . cc ('║', 'N') . "\n";
    }
    echo cc ('╠═══════╬' . str_repeat ('═', CLI_LEN - 12 - 20) . '╩' . str_repeat ('═', 21) . '╣', 'N') . "\n";
    echo cc ('║', 'N') . '  選項 ' . cc ('║', 'N') . ' 功能 ' . str_repeat (' ', CLI_LEN - 16) . cc ('║', 'N') . "\n";
    echo cc ('╟───────╫' . str_repeat ('─', CLI_LEN - 12 - 20) . '─' . str_repeat ('─', 21) . '╢', 'N') . "\n";
    echo cc ('║', 'N') . ' ' . cc ($cho == '1' ? '➜' : ' ', 'y') . cc ('  1. ', $cho == '1' ? 'Y' : null) . cc ('║', 'N') . cc (' 更新至最新版 ', $cho == '1' ? 'Y' : null) . str_repeat (' ', CLI_LEN - 13 - 11) . cc ('║', 'N') . "\n";
    echo cc ('║', 'N') . ' ' . cc ($cho == '2' ? '➜' : ' ', 'y') . cc ('  2. ', $cho == '2' ? 'Y' : null) . cc ('║', 'N') . cc (' 輸入更新版號 ', $cho == '2' ? 'Y' : null) . str_repeat (' ', CLI_LEN - 13 - 11) . cc ('║', 'N') . "\n";
    echo cc ('╟ ─ ─ ─ ╫ ' . str_repeat ('─ ', (CLI_LEN - 11) / 2) . '─║', 'N') . "\n";
    echo cc ('║', 'N') . cc ('    q. ', 'W') . cc ('║', 'N') . ' 沒事，按錯.. 離開本程式 ' . str_repeat (' ', CLI_LEN - 23 - 12) . cc ('║', 'N') . "\n";
    echo cc ('╚═══════╩' . str_repeat ('═', CLI_LEN - 10) . '╝', 'N') . "\n";

    return true;
  }
}

if (!function_exists ('cho1')) {
  function cho1 ($version = null) {
    $cho = 1;
    $now = MigrationTool::nowVersion ();
    $keys = array_keys (MigrationTool::files (true));
    
    if ($version !== null) is_numeric ($version) && $version >= 0 && $version <= end ($keys) ? $cho = '2' : exit ("\n" . cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (' 警告！ ', 'Y', 'r') . cc ('版本「', null, 'r') . cc ($version, 'W', 'r') . cc ('」是錯誤的版號，請使用正確的版號', null, 'r') . cc ('(0 ~ ' . end ($keys) . ')', 'W', 'r') .  cc (str_repeat (' ', CLI_LEN - 55), null, 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n\n");
    else $version = end ($keys);

    headerText ($cho);

    if (($err = MigrationTool::to ($version)) === true)
      headerText ($cho) && exit ("\n " . cc ('◎', 'G') . " Migration 更新中，正在由第 " . cc ($now, 'W') . ' 版更新至第' . cc ($version, 'W') . ' 版.. ' . cc ('更新成功', 'g') . "。\n\n " . cc ('◎', 'G') . ' 目前已經更新至第 ' . cc (MigrationTool::nowVersion (), 'W') . ' 版了！' . "\n\n");

    headerText ($cho) && exit ("\n " . cc ('◎', 'G') . " Migration 更新中，正在由第 " . cc ($now, 'W') . ' 版更新至第' . cc ($version, 'W') . ' 版.. ' . cc ('更新失敗', 'r') . "。\n\n" . implode ("\n", array_map (function ($e) { return ' ' . cc ('◎', 'G') . ' ' . $e . "\n"; }, $err)) . "\n " . cc ('◎', 'G') . ' 目前在 ' . cc (MigrationTool::nowVersion (), 'W') . ' 版。' . "\n\n");
  }
}


if (!function_exists ('cho2')) {
  function cho2 () {
    $keys = array_keys (MigrationTool::files (true));
    $check = $version = '';
    
    do {
      headerText ('2');
      echo "\n " . cc ('◎', 'G') . " 請輸入要更新的版本號" . cc ('(0 ~ ' . end ($keys) . ')', 'N') . "：" . (is_numeric ($version) && $version >= 0 && $version <= end ($keys) ? $version . "\n" : '');
      is_numeric ($version = is_numeric ($version) && $version >= 0 && $version <= end ($keys) ? $version : trim (fgets (STDIN))) || $version = '';

      if (is_numeric ($version) && $version >= 0 && $version <= end ($keys)) {
        echo "\n " . cc ('➜', 'R') . ' 您確定要更新至「' . cc ($version, 'W') . '」' . cc ('[Y：沒錯, n：不是]', 'N') . '？';
        ($check = strtolower (trim (fgets (STDIN)))) == 'n' && $version = '';
      }
    } while ($check != 'y');
    
    cho1 ($version);
  }
}