<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('word_limiter')) {
  function word_limiter ($str, $limit = 100, $end_char = '&#8230;') {
    if (trim ($str) === '')
      return $str;

    preg_match ('/^\s*+(?:\S++\s*+){1,' . (int)$limit . '}/', $str, $matches);

    if (strlen ($str) === strlen ($matches[0]))
      $end_char = '';

    return rtrim ($matches[0]) . $end_char;
  }
}

if (!function_exists ('character_limiter')) {
  function character_limiter ($str, $n = 500, $end_char = '&#8230;') {
    if (mb_strlen ($str) < $n)
      return $str;

    $str = preg_replace ('/ {2,}/', ' ', str_replace (array ("\r", "\n", "\t", "\v", "\f"), ' ', $str));

    if (mb_strlen ($str) <= $n)
      return $str;

    $out = '';
    foreach (explode (' ', trim ($str)) as $val) {
      $out .= $val.' ';

      if (mb_strlen ($out) >= $n) {
        $out = trim ($out);
        return mb_strlen ($out) === mb_strlen ($str) ? $out : $out . $end_char;
      }
    }
  }
}

if (!function_exists ('ascii_to_entities')) {
  function ascii_to_entities ($str) {
    $out = '';
    $length = defined ('MB_OVERLOAD_STRING') ? mb_strlen ($str, '8bit') - 1 : strlen ($str) - 1;

    for ($i = 0, $count = 1, $temp = array (); $i <= $length; $i++) {
      $ordinal = ord ($str[$i]);
      if ($ordinal < 128) {
        if (count ($temp) === 1) {
          $out .= '&#' . array_shift ($temp) . ';';
          $count = 1;
        }

        $out .= $str[$i];
      } else {
        if (count ($temp) === 0)
          $count = ($ordinal < 224) ? 2 : 3;

        array_push ($temp, $ordinal);

        if (count ($temp) === $count) {
          $number = ($count === 3) ? (($temp[0] % 16) * 4096) + (($temp[1] % 64) * 64) + ($temp[2] % 64) : (($temp[0] % 32) * 64) + ($temp[1] % 64);
          $out .= '&#' . $number . ';';
          $count = 1;
          $temp = array ();
        } else if ($i === $length) {
          $out .= '&#' . implode (';', $temp) . ';';
        }
      }
    }

    return $out;
  }
}

if (!function_exists ('entities_to_ascii')) {
  function entities_to_ascii ($str, $all = true) {
    if (preg_match_all ('/\&#(\d+)\;/', $str, $matches))
      for ($i = 0, $s = count ($matches[0]); $i < $s; $i++) {
        $digits = $matches[1][$i];
        $out = '';

        if ($digits < 128)
          $out .= chr ($digits);
        else if ($digits < 2048)
          $out .= chr (192 + (($digits - ($digits % 64)) / 64)) . chr (128 + ($digits % 64));
        else
          $out .= chr (224 + (($digits - ($digits % 4096)) / 4096)) . chr (128 + ((($digits % 4096) - ($digits % 64)) / 64)) . chr (128 + ($digits % 64));
        
        $str = str_replace ($matches[0][$i], $out, $str);
      }

    return $all ? str_replace (array ('&amp;', '&lt;', '&gt;', '&quot;', '&apos;', '&#45;'), array ('&', '<', '>', '"', "'", '-'), $str) : $str;
  }
}

if (!function_exists ('word_censor')) {
  function word_censor ($str, $censored, $replacement = '') {
    if (!is_array ($censored))
      return $str;

    $str = ' ' . $str . ' ';
    $delim = '[-_\'\"`(){}<>\[\]|!?@#%&,.:;^~*+=\/ 0-9\n\r\t]';

    foreach ($censored as $badword) {
      $badword = str_replace ('\*', '\w*?', preg_quote ($badword, '/'));

      if ($replacement !== '') {
        $str = preg_replace ("/({$delim})(" . $badword . ")({$delim})/i", "\\1{$replacement}\\3", $str);
      } else if (preg_match_all ("/{$delim}(" . $badword . "){$delim}/i", $str, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE)) {
        $matches = $matches[1];
        
        for ($i = count ($matches) - 1; $i >= 0; $i--) {
          $length = strlen ($matches[$i][0]);
          $str = substr_replace ($str, str_repeat ('#', $length), $matches[$i][1], $length);
        }
      }
    }

    return trim ($str);
  }
}

if (!function_exists ('highlight_code')) {
  function highlight_code ($str) {
    $str = str_replace (array ('&lt;', '&gt;', '<?', '?>', '<%', '%>', '\\', '</script>'), array ('<', '>', 'phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'), $str);
    $str = highlight_string ('<?php ' . $str . ' ?>', true);
    $str = preg_replace (array ('/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i', '/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is', '/<span style="color: #[A-Z0-9]+"\><\/span>/i'), array ('<span style="color: #$1">', "$1</span>\n</span>\n</code>", ''), $str);

    return str_replace (array ('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'), array ('&lt;?', '?&gt;', '&lt;%', '%&gt;', '\\', '&lt;/script&gt;'), $str);
  }
}

if (!function_exists ('highlight_phrase')) {
  function highlight_phrase ($str, $phrase, $tag_open = '<mark>', $tag_close = '</mark>') {
    return ($str !== '' && $phrase !== '') ? preg_replace ('/(' . preg_quote ($phrase, '/') . ')/i' . (UTF8_ENABLED ? 'u' : ''), $tag_open . '\\1' . $tag_close, $str) : $str;
  }
}

if (!function_exists ('convert_accented_characters')) {
  function convert_accented_characters ($str) {
    static $array_from, $array_to;

    if (!is_array ($array_from)) {
      if (!$foreign_characters = config ('foreign_chars')){
        $array_from = array ();
        $array_to = array ();
        return $str;
      }

      $array_from = array_keys ($foreign_characters);
      $array_to = array_values ($foreign_characters);
    }

    return preg_replace ($array_from, $array_to, $str); }
}

if (!function_exists ('word_wrap')) {
  function word_wrap ($str, $charlim = 76) {
    is_numeric ($charlim) || $charlim = 76;

    $str = preg_replace ('| +|', ' ', $str);

    if (strpos ($str, "\r") !== false)
      $str = str_replace (array ("\r\n", "\r"), "\n", $str);

    $unwrap = array ();
    if (preg_match_all ('|\{unwrap\}(.+?)\{/unwrap\}|s', $str, $matches))
      for ($i = 0, $c = count ($matches[0]); $i < $c; $i++) {
        array_push ($unwrap, $matches[1][$i]);
        $str = str_replace ($matches[0][$i], '{{unwrapped' . $i . '}}', $str);
      }

    $str = wordwrap ($str, $charlim, "\n", false);

    $output = '';
    foreach (explode ("\n", $str) as $line) {
      if (mb_strlen ($line) <= $charlim) {
        $output .= $line . "\n";
        continue;
      }

      $temp = '';
      while (mb_strlen ($line) > $charlim) {
        if (preg_match ('!\[url.+\]|://|www\.!', $line))
          break;

        $temp .= mb_substr ($line, 0, $charlim - 1);
        $line = mb_substr ($line, $charlim - 1);
      }

      $output .= (($temp !== '' ? $temp . "\n" . $line : $line) . "\n");
    }

    if ($unwrap)
      foreach ($unwrap as $key => $val)
        $output = str_replace ('{{unwrapped' . $key . '}}', $val, $output);

    return $output;
  }
}

if (!function_exists ('ellipsize')) {
  function ellipsize ($str, $max_length, $position = 1, $ellipsis = '&hellip;') {
    $str = trim (strip_tags ($str));

    if (mb_strlen ($str) <= $max_length)
      return $str;

    $beg = mb_substr ($str, 0, floor ($max_length * $position));
    $position = $position > 1 ? 1 : $position;
    $end = $position === 1 ? mb_substr ($str, 0, -($max_length - mb_strlen ($beg))) : mb_substr ($str, -($max_length - mb_strlen ($beg)));

    return $beg . $ellipsis . $end;
  }
}
