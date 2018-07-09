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
    echo cc ('╔' . str_repeat ('═', CLI_LEN - 2) . '╗', 'N') . "\n";
    echo cc ('║' . str_repeat (' ', CLI_LEN - 2) . '║', 'N') . "\n";
    echo cc ('║', 'N') . cc ('  歡迎使用 OACI Create 工具', 'y') . cc (' v1.0', 'N') . str_repeat (' ', CLI_LEN - 34) . cc ('║', 'N') . "\n";
    echo cc ('║', 'N') . str_repeat (' ', CLI_LEN - 15) . '    ' . cc ('by', 'N') . ' ' . cc ('OA Wu ', 'W') . cc ('║', 'N') . "\n";
    echo cc ('╠══════╦' . str_repeat ('═', CLI_LEN - 9) . '╣', 'N') . "\n";
    echo cc ('║', 'N') . ' 選項 ' . cc ('║', 'N') . ' 名稱 ' . str_repeat (' ', CLI_LEN - 15) . cc ('║', 'N') . "\n";
    echo cc ('╟──────╫' . str_repeat ('─', CLI_LEN - 9) . '╢', 'N') . "\n";
    echo cc ('║', 'N') . ' ' . cc ($cho == '1' ? '➜' : ' ', 'y') . cc (' 1. ', $cho == '1' ? 'Y' : null) . cc ('║', 'N') . cc (' Controller ', $cho == '1' ? 'Y' : null) . str_repeat (' ', CLI_LEN - strlen ('Controller') - 11) . cc ('║', 'N') . "\n";
    echo cc ('║', 'N') . ' ' . cc ($cho == '2' ? '➜' : ' ', 'y') . cc (' 2. ', $cho == '2' ? 'Y' : null) . cc ('║', 'N') . cc (' Model ', $cho == '2' ? 'Y' : null) . str_repeat (' ', CLI_LEN - strlen ('Model') - 11) . cc ('║', 'N') . "\n";
    echo cc ('║', 'N') . ' ' . cc ($cho == '3' ? '➜' : ' ', 'y') . cc (' 3. ', $cho == '3' ? 'Y' : null) . cc ('║', 'N') . cc (' Migration ', $cho == '3' ? 'Y' : null) . str_repeat (' ', CLI_LEN - strlen ('Migration') - 11) . cc ('║', 'N') . "\n";
    echo cc ('╟ ─ ─ ─║─' . str_repeat (' ─', (CLI_LEN - 9) / 2) . '║', 'N') . "\n";
    echo cc ('║', 'N') . cc ('   q. ', 'W') . cc ('║', 'N') . ' 沒事，按錯.. 離開本程式 ' . str_repeat (' ', CLI_LEN - 23 - 11) . cc ('║', 'N') . "\n";
    echo cc ('╚══════╩' . str_repeat ('═', CLI_LEN - 9) . '╝', 'N') . "\n";
  }
}

if (!function_exists ('toModelName')) {
function toModelName ($name) {
  return ucfirst (camelize (singular ($name)));
}
}
if (!function_exists ('checkModelExist')) {
function checkModelExist ($name) {
  return $name ? file_exists (APPPATH . 'model' . DIRECTORY_SEPARATOR . toModelName ($name) . EXT) : false;
}
}

if (!function_exists ('checkColumnHasDouble')) {
  function checkColumnHasDouble ($c1, $c2) {
    if (!$c1)
      return false;
    
    is_array ($c2) || $c2 = preg_split ('/\s+/', $c2);

    if (!$c2)
      return false;

    foreach ($c2 as $c)
      if (in_array ($c, $c1))
        return true;

    return false;
  }
}

if (!function_exists ('createModel')) {
  function createModel ($name, $imgUpload, $fileUpload) {
    $imgUpload = array_map (function ($t) use ($name) { return array ($t, $name . toModelName ($t), 'ImageUploader'); }, $imgUpload);
    $fileUpload = array_map (function ($t) use ($name) { return array ($t, $name . toModelName ($t), 'FileUploader'); }, $fileUpload);
    return "<?php defined ('OACI') || exit ('此檔案不允許讀取。');\n" . "\n" . "/**\n" . " * @author      OA Wu <comdan66@gmail.com>\n" . " * @copyright   Copyright (c) 2013 - " . date ('Y') . ", OACI\n" . " * @license     http://opensource.org/licenses/MIT  MIT License\n" . " * @link        https://www.ioa.tw/\n" . " */\n" . "\n" . "class " . $name . " extends Model {\n" . "  static \$table_name = '" . strtolower (plural (preg_replace ('/\B([A-Z])/', '_$1', $name))) . "';\n" . "\n" . "  static \$has_one = array (\n" . "  );\n" . "\n" . "  static \$has_many = array (\n" . "  );\n" . "\n" . "  static \$belongs_to = array (\n" . "  );\n" . "\n" . "  public function __construct (\$attrs = array (), \$guardAttrs = true, \$instantiatingViafind = false, \$newRecord = true) {\n" . "    parent::__construct (\$attrs, \$guardAttrs, \$instantiatingViafind, \$newRecord);\n" . ($imgUpload ? "\n    // 設定圖片上傳器\n" . implode ("", array_map (function ($t) { return "    Uploader::bind ('" . $t[0] . "', '" . $t[1] . $t[2] . "');\n"; }, $imgUpload)) : '') . ($fileUpload ? "\n    // 設定檔案上傳器\n" . implode ("", array_map (function ($t) { return "    Uploader::bind ('" . $t[0] . "', '" . $t[1] . $t[2] . "');\n"; }, $fileUpload)) : '') . "  }\n" . "\n  public function destroy () {" . "\n    if (!isset (\$this->id))" . "\n      return false;" . "\n    " . "\n    return \$this->delete ();" . "\n  }\n" . ($imgUpload || $fileUpload ? "\n  public function putFiles (\$files) {" . "\n    foreach (\$files as \$key => \$file)" . "\n      if (isset (\$files[\$key]) && \$files[\$key] && isset (\$this->\$key) && \$this->\$key instanceof Uploader && !\$this->\$key->put (\$files[\$key]))" . "\n        return false;" . "\n    return true;" . "\n  }\n" : "") . "}\n" . ($imgUpload ? "\n/* -- 圖片上傳器物件 ------------------------------------------------------------------ */\n" . implode ('', array_map (function ($t) { return "class " . $t[1] . $t[2] . " extends " . $t[2] . " {\n" . "  public function getVersions () {\n" . "    return array (\n" . "        '' => array (),\n" . "        'w100' => array ('resize', 100, 100, 'width'),\n" . "        'c1200x630' => array ('adaptiveResizeQuadrant', 1200, 630, 't'),\n" . "      );\n" . "  }\n" . "}\n"; }, $imgUpload)) : '') . ($fileUpload ? "\n/* -- 檔案上傳器物件 ------------------------------------------------------------------ */\n" . implode ('', array_map (function ($t) { return "class " . $t[1] . $t[2] . " extends " . $t[2] . " {\n" . "}\n"; }, $fileUpload)) : '');
  }
}

if (!function_exists ('cho2final')) {
  function cho2final ($name, $imgUpload, $fileUpload) {
    headerText ('2');
    echo cc ('╔' . str_repeat ('═', CLI_LEN - 2) . '╗', 'N') . "\n";
    echo cc ('║', 'N') . cc (' Model 的資訊', 'y') . str_repeat (' ', CLI_LEN - 15) . cc ('║', 'N') . "\n";
    echo cc ('╠════════════╦' . str_repeat ('═', CLI_LEN - 15) . '╣', 'N') . "\n";
    echo cc ('║', 'N') . '       標題 ' . cc ('║', 'N') . ' 內容 ' . str_repeat (' ', CLI_LEN - 21) . cc ('║', 'N') . "\n";
    echo cc ('╟────────────╫' . str_repeat ('─', CLI_LEN - 15) . '╢', 'N') . "\n";
    echo cc ('║', 'N') . ' ' . 'Model 名稱 ' . cc ('║', 'N') . sprintf (' %-75s', cc ($name, 'W')) . cc ('║', 'N') . "\n";
    echo cc ('╟ ─ ─ ─ ─ ─ ─║─' . str_repeat (' ─', (CLI_LEN - 15) / 2) . '║', 'N') . "\n";
    echo cc ('║', 'N') . ' ' . '圖片上傳器 ' . cc ('║', 'N') . sprintf (' %-' . (63 + ($imgUpload ? ($t = count ($imgUpload)) * 12 + ($t - 1) * 11 : 13)) . 's', $imgUpload ? implode (cc ('、', 'N'), array_map (function ($t) { return cc ($t, 'W'); }, $imgUpload)) : cc ('無', 'N')) . cc ('║', 'N') . "\n";
    echo cc ('╟ ─ ─ ─ ─ ─ ─║─' . str_repeat (' ─', (CLI_LEN - 15) / 2) . '║', 'N') . "\n";
    echo cc ('║', 'N') . ' ' . '檔案上傳器 ' . cc ('║', 'N') . sprintf (' %-' . (63 + ($fileUpload ? ($t = count ($fileUpload)) * 12 + ($t - 1) * 11 : 13)) . 's', $fileUpload ? implode (cc ('、', 'N'), array_map (function ($t) { return cc ($t, 'W'); }, $fileUpload)) : cc ('無', 'N')) . cc ('║', 'N') . "\n";
    echo cc ('╚════════════╩' . str_repeat ('═', CLI_LEN - 15) . '╝', 'N') . "\n";
    echo "\n " . cc ('➜', 'R') . ' 以上資訊是否正確' . cc ('[Y：沒錯, n：重新填寫]', 'N') . '？';
  }
}

if (!function_exists ('cho2')) {
  function cho2 () {
    is_really_writable (APPPATH . 'model') || exit ("\n" . cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (' 警告！ ', 'Y', 'r') . cc ('您的 Model 資料夾沒有讀寫權限。' . str_repeat (' ', CLI_LEN - 39), 'W', 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n\n");

    do {
      $name = $check ='';

      do {
        headerText ('2');

        echo "\n";
        if ($r = checkModelExist ($name)) {
          echo cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (' 警告！ ', 'Y', 'r') . cc ('Model 名稱「', null, 'r') . cc ($name, 'W', 'r') . cc ('」已經存在，請重新輸入！' .  str_repeat (' ', CLI_LEN - 44 - strlen ($name)), null, 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n\n";
          $name = '';
        }

        echo ' ' . cc ('➜', 'R') . ' 請輸入要新增的 Model 名稱' . cc ('(離開請按 control + c)', 'N') . '：' . (!$r && $name ? $name . "\n" : '');
        
        if (!$name && (($name = trim (fgets (STDIN))) && checkModelExist ($name)))
          continue;

        if ($name) {
          echo "\n " . cc ('➜', 'R') . ' Midel 名稱為「' . cc (toModelName ($name), 'W') . '」是否正確' . cc ('[Y：沒錯, n：不是]', 'N') . '？';
          ($check = strtolower (trim (fgets (STDIN)))) == 'n' && $name = '';
        }
      } while ($check != 'y');

      $name = toModelName ($name);

      do {
        headerText ('2');
        echo "\n ". cc ('◎', 'G') . ' Model 名稱：「' . cc ($name, 'W') . '」' . "\n\n " . cc ('➜', 'R') . ' 是否有欄位綁定「' . cc ('圖片上傳器', 'W') . '」' . cc ('[Y：有的, n：沒有]', 'N') . '？';
      } while (!in_array ($imgUpload = strtolower (trim (fgets (STDIN))), array ('y', 'n')));

      if ($imgUpload == 'y') {
        $check = '';
        $imgUpload = array ();

        do {
          headerText ('2');
          echo "\n " . cc ('◎', 'G') . ' Model 名稱：「' . cc ($name, 'W') . '」' . "\n\n " . cc ('➜', 'R') . ' 請輸入欄位名稱' . cc ('(多欄位用空白鍵隔開)', 'N') . '：' . ($imgUpload ? ($imgUpload = implode (' ', $imgUpload)) . "\n" : '');

          if ($imgUpload || ($imgUpload = trim (fgets (STDIN)))) {
            $imgUpload = array_unique (preg_split ('/\s+/', $imgUpload));
            echo "\n" . ' ' . cc ('➜', 'R') . ' 欄位有「' . implode ('、', array_map (function ($t) { return cc ($t, 'W'); }, $imgUpload)) . '」是否正確' . cc ('[Y：沒錯, n：不是]', 'N') . '？';
            ($check = strtolower (trim (fgets (STDIN)))) == 'n' && $imgUpload = '';
          }

        } while ($check != 'y');
      } else {
        $imgUpload = array ();
      }

      do {
        headerText ('2');
        echo "\n " . cc ('◎', 'G') . ' Model 名稱：「' . cc ($name, 'W') . '」' . "\n\n " . cc ('◎', 'G') . ' 圖片上傳器：' . ($imgUpload ? '「' . implode ('、', array_map (function ($t) { return cc ($t, 'W'); }, $imgUpload)) . '」' : cc ('無', 'N')) . "\n\n " . cc ('➜', 'R') . ' 是否有欄位綁定「' . cc ('檔案上傳器', 'W') . '」' . cc ('[Y：有的, n：沒有]', 'N') . '？';
      } while (!in_array ($fileUpload = strtolower (trim (fgets (STDIN))), array ('y', 'n')));

      if ($fileUpload == 'y') {
        $check = '';
        $fileUpload = array ();

        do {
          headerText ('2');
          echo "\n " . cc ('◎', 'G') . ' Model 名稱：「' . cc ($name, 'W') . '」' . "\n\n " . cc ('◎', 'G') . ' 圖片上傳器：' . ($imgUpload ? '「' . implode ('、', array_map (function ($t) { return cc ($t, 'W'); }, $imgUpload)) . '」' : cc ('無', 'N')) . "\n\n";

          if ($r = checkColumnHasDouble ($imgUpload, $fileUpload)) {
            echo cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (' 警告！ ', 'Y', 'r') . cc ('有欄位與圖片上傳器相衝突！ ', 'W', 'r') . cc (str_repeat (' ', CLI_LEN - 35), null, 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n\n";
            $fileUpload = array ();
          }

          echo ' ' . cc ('➜', 'R') . ' 請輸入欄位名稱' . cc ('(多欄位用空白鍵隔開)', 'N') . '：' . (!$r && $fileUpload ? ($fileUpload = implode (' ', $fileUpload)) . "\n" : '');

          if (!$fileUpload && (($fileUpload = trim (fgets (STDIN))) && checkColumnHasDouble ($imgUpload, $fileUpload)))
            continue;

          if ($fileUpload) {
            $fileUpload = array_unique (preg_split ('/\s+/', $fileUpload));

            echo "\n " . cc ('➜', 'R') . ' 欄位有「' . implode ('、', array_map (function ($t) { return cc ($t, 'W'); }, $fileUpload)) . '」是否正確' . cc ('[Y：沒錯, n：不是]', 'N') . '？';
            ($check = strtolower (trim (fgets (STDIN)))) == 'n' && $fileUpload = '';
          }
        } while ($check != 'y');
      } else {
        $fileUpload = array ();
      }

      do {
        cho2final ($name, $imgUpload, $fileUpload);
      } while (!in_array ($fin = strtolower (trim (fgets (STDIN))), array ('y', 'n')));
    } while ($fin != 'y');
    
    exit ("\n" . (checkModelExist ($name) ? cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (' 警告！ ', 'Y', 'r') . cc ('Model 名稱「', null, 'r') . cc ($name, 'W', 'r') . cc ('」已經存在！' .  str_repeat (' ', CLI_LEN - 32 - strlen ($name)), null, 'r') . "\n" . cc (str_repeat (' ', CLI_LEN), 'N', 'r') . "\n" . cc (str_repeat ('─', CLI_LEN), 'W', 'r') . "\n\n" : cc (str_repeat ('─ ', CLI_LEN / 2), 'N') . "\n\n" . ' ' . cc ('◎', 'G') . " Model 位置：" . cc (APPPATH . 'model' . DIRECTORY_SEPARATOR . toModelName ($name) . EXT, 'W') . "\n" . "\n " . cc ('◎', 'G') . " 新增 Model「" . cc (toModelName ($name), 'W') . "」- " . (write_file (APPPATH . 'model' . DIRECTORY_SEPARATOR . toModelName ($name) . EXT, createModel ($name, $imgUpload, $fileUpload), 'x') ? cc ('成功', 'g') : cc ('失敗', 'r')) . "\n" . "\n " . cc ('◎', 'G') . ' 圖片上傳器欄位：' . ($imgUpload ? implode ('、', array_map (function ($t) { return cc ($t, 'W'); }, $imgUpload)) : cc ('無', 'N')) . "\n" . "\n " . cc ('◎', 'G') . ' 檔案上傳器欄位：' . ($fileUpload ? implode ('、', array_map (function ($t) { return cc ($t, 'W'); }, $fileUpload)) : cc ('無', 'N')) . "\n" . "\n" . cc (str_repeat ('─ ', CLI_LEN / 2), 'N') . "\n\n"));
  }
}

if (!function_exists ('cho3')) {
  function cho3 () {
    $check = $name = '';

    do {
      headerText ('3');

      echo "\n " . cc ('➜', 'R') . ' 請輸入要建立的檔名' . cc ('(離開請按 control + c)', 'N') . '：' . ($name ? $name . "\n" : '');

      if ($name || ($name = trim (fgets (STDIN)))) {
        echo "\n " . cc ('➜', 'R') . ' 檔名「' . cc ($name, 'W') . '」是否正確' . cc ('[Y：沒錯, n：不是]', 'N') . '？';

        ($check = strtolower (trim (fgets (STDIN)))) == 'n' && $name = '';
      }
    } while ($check != 'y');
    
    exit ("\n " . cc ('◎', 'G') . ' Migration「' . cc ($name, 'W') . '」建立中.. ' . (MigrationTool::create ($name, $err) ? cc ('成功！', 'g') . "\n\n" . ' ' . cc ('◎', 'G') . ' 已經成功建立 Migration：' . cc ($err, 'W') . "\n\n" : cc ('失敗！', 'r') . "\n\n" . ' ' . cc ('◎', 'G') . ' 錯誤原因：' . cc ($err, 'W') . "\n\n"));
  }
}