<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Security {
  private static $charset;
  private static $xssHash;
  private static $entities;

  private static $neverAllowedStr;
  private static $neverAllowedRegex;
  private static $naughtyTags;
  private static $evilAttributes;
  private static $filenameBadChars;

  public static function init () {
    self::$charset = strtoupper (config ('other', 'charset'));
  }  

  public static function getRandomBytes ($length) {
    if (!($length && is_numeric ($length) && ctype_digit ((string) $length)))
      return false;

    if (function_exists ('random_bytes')) {
      try {
        return random_bytes ((int) $length);
      } catch (Exception $e) {
        return false;
      }
    }

    if (defined ('MCRYPT_DEV_URANDOM') && ($output = mcrypt_create_iv ($length, MCRYPT_DEV_URANDOM)) !== false)
      return $output;

    if (is_readable ('/dev/urandom') && ($fp = fopen('/dev/urandom', 'rb')) !== false) {
      is_php ('5.4') && stream_set_chunk_size ($fp, $length);
      $output = fread ($fp, $length);
      fclose ($fp);
      
      if ($output !== false)
        return $output;
    }

    if (function_exists ('openssl_random_pseudo_bytes'))
      return openssl_random_pseudo_bytes ($length);

    return false;
  }

  public static function urldecodespaces ($matches) {
    $input = $matches[0];
    $nospaces = preg_replace ('#\s+#', '', $input);
    return $nospaces === $input ? $input : rawurldecode ($nospaces);
  }

  public static function convertAttribute ($match) {
    return str_replace (array ('>', '<', '\\'), array ('&gt;', '&lt;', '\\\\'), $match[0]);
  }

  public static function xssHash () {
    if (self::$xssHash === null) {
      $rand = self::getRandomBytes (16);
      self::$xssHash = ($rand === false) ? md5 (uniqid (mt_rand (), true)) : bin2hex ($rand);
    }

    return self::$xssHash;
  }

  public static function entityDecode ($str, $charset = null) {
    if (strpos ($str, '&') === false) return $str;

    $charset = !isset ($charset) ? self::$charset : $charset;
    $flag = is_php ('5.4') ? ENT_COMPAT | ENT_HTML5 : ENT_COMPAT;

    if (!isset (self::$entities)) {
      self::$entities = array_map ('strtolower', get_html_translation_table (HTML_ENTITIES, $flag, $charset));

      if ($flag === ENT_COMPAT) {
        self::$entities[':'] = '&colon;';
        self::$entities['('] = '&lpar;';
        self::$entities[')'] = '&rpar;';
        self::$entities["\n"] = '&NewLine;';
        self::$entities["\t"] = '&Tab;';
      }
    }

    do {
      $str_compare = $str;

      if (preg_match_all ('/&[a-z]{2,}(?![a-z;])/i', $str, $matches)) {
        $replace = array ();
        $matches = array_unique (array_map ('strtolower', $matches[0]));

        foreach ($matches as &$match)
          if (($char = array_search ($match . ';', self::$entities, true)) !== false)
            $replace[$match] = $char;

        $str = str_replace (array_keys ($replace), array_values ($replace), $str);
      }

      $str = html_entity_decode (preg_replace ('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str), $flag, $charset);
      $flag === ENT_COMPAT && $str = str_replace (array_values (self::$entities), array_keys (self::$entities), $str);
    } while ($str_compare !== $str);

    return $str;
  }

  public static function decodeEntity ($match) {
    $match = preg_replace ('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i', self::xssHash () . '\\1=\\2', $match[0]);
    return str_replace (self::xssHash (), '&', self::entityDecode ($match, self::$charset));
  }
  private static function doNeverAllowed ($str) {
    self::$neverAllowedStr || self::$neverAllowedStr = array ('document.cookie' => '[removed]', 'document.write' => '[removed]', '.parentNode' => '[removed]', '.innerHTML' => '[removed]', '-moz-binding' => '[removed]', '<!--' => '&lt;!--', '-->' => '--&gt;', '<![CDATA[' => '&lt;![CDATA[', '<comment>' => '&lt;comment&gt;', '<%' => '&lt;&#37;');
    self::$neverAllowedRegex || self::$neverAllowedRegex = array ('javascript\s*:', '(document|(document\.)?window)\.(location|on\w*)', 'expression\s*(\(|&\#40;)', 'vbscript\s*:', 'wscript\s*:', 'jscript\s*:', 'vbs\s*:', 'Redirect\s+30\d', "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?");

    $str = str_replace (array_keys (self::$neverAllowedStr), self::$neverAllowedStr, $str);
    foreach (self::$neverAllowedRegex as $regex)
      $str = preg_replace ('#' . $regex . '#is', '[removed]', $str);

    return $str;
  }
  
  public static function compactExplodedWords ($matches) {
    return preg_replace ('/\s+/s', '', $matches[1]) . $matches[2];
  }

  public static function filterAttributes ($str) {
    $out = '';
    if (preg_match_all ('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
      foreach ($matches[0] as $match)
        $out .= preg_replace ('#/\*.*?\*/#s', '', $match);

    return $out;
  }
  public static function jsLinkRemoval ($match) {
    return str_replace ($match[1], preg_replace ('#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|d\s*a\s*t\s*a\s*:)#si', '', self::filterAttributes ($match[1])), $match[0]);
  }
  public static function jsImgRemoval ($match) {
    return str_replace ($match[1], preg_replace ('#src=.*?(?:(?:alert|prompt|confirm|eval)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si', '', self::filterAttributes ($match[1])), $match[0]);
  }
  public static function sanitizeNaughtyHtml ($matches) {
    self::$naughtyTags || self::$naughtyTags = array ('alert', 'area', 'prompt', 'confirm', 'applet', 'audio', 'basefont', 'base', 'behavior', 'bgsound', 'blink', 'body', 'embed', 'expression', 'form', 'frameset', 'frame', 'head', 'html', 'ilayer', 'iframe', 'input', 'button', 'select', 'isindex', 'layer', 'link', 'meta', 'keygen', 'object', 'plaintext', 'style', 'script', 'textarea', 'title', 'math', 'video', 'svg', 'xml', 'xss');
    self::$evilAttributes || self::$evilAttributes = array ('on\w+', 'style', 'xmlns', 'formaction', 'form', 'xlink:href', 'FSCommand', 'seekSegmentTime');

    if (empty ($matches['closeTag']))
      return '&lt;' . $matches[1];

    if (in_array(strtolower($matches['tagName']), self::$naughtyTags, true))
      return '&lt;'.$matches[1].'&gt;';

    if (isset ($matches['attributes'])) {
      $attributes = array ();
      $attributes_pattern = '#' . '(?<name>[^\s\042\047>/=]+)' . '(?:\s*=(?<value>[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*)))' . '#i';
      $is_evil_pattern = '#^(' . implode ('|', self::$evilAttributes) . ')$#i';

      do {
        $matches['attributes'] = preg_replace ('#^[^a-z]+#i', '', $matches['attributes']);

        if (!preg_match ($attributes_pattern, $matches['attributes'], $attribute, PREG_OFFSET_CAPTURE))
          break;

        if (preg_match ($is_evil_pattern, $attribute['name'][0]) || (trim ($attribute['value'][0]) === ''))
          array_push ($attributes, 'xss=removed');
        else
          array_push ($attributes, $attribute[0][0]);

        $matches['attributes'] = substr ($matches['attributes'], $attribute[0][1] + strlen ($attribute[0][0]));
      } while ($matches['attributes'] !== '');

      $attributes = empty ($attributes) ? '' : ' ' . implode (' ', $attributes);

      return '<' . $matches['slash'] . $matches['tagName'] . $attributes . '>';
    }

    return $matches[0];
  }
  public static function xssClean ($str, $isImage = false) {
    if (is_array ($str)) {
      foreach ($str as $key => &$value)
        $str[$key] = self::xssClean ($value);
      return $str;
    }

    $str = remove_invisible_characters ($str);

    if (stripos ($str, '%') !== false) {
      do {
        $oldstr = $str;
        $str = rawurldecode ($str);
        $str = preg_replace_callback ('#%(?:\s*[0-9a-f]){2,}#i', array ('Security', 'urldecodespaces'), $str);
      } while ($oldstr !== $str);
      unset ($oldstr);
    }

    $str = preg_replace_callback ("/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si", array ('Security', 'convertAttribute'), $str);
    $str = preg_replace_callback ('/<\w+.*/si', array ('Security', 'decodeEntity'), $str);

    $str = remove_invisible_characters ($str);

    $str = str_replace ("\t", ' ', $str);

    $converted_string = $str;

    $str = self::doNeverAllowed ($str);

    $str = $isImage === true ? preg_replace ('/<\?(php)/i', '&lt;?\\1', $str) : str_replace (array ('<?', '?'.'>'), array ('&lt;?', '?&gt;'), $str);

    $words = array ('javascript', 'expression', 'vbscript', 'jscript', 'wscript', 'vbs', 'script', 'base64', 'applet', 'alert', 'document', 'write', 'cookie', 'window', 'confirm', 'prompt', 'eval');

    foreach ($words as $word) {
      $word = implode ('\s*', str_split ($word)) . '\s*';
      $str = preg_replace_callback ('#(' . substr ($word, 0, -3) . ')(\W)#is', array ('Security', 'compactExplodedWords'), $str);
    }

    do {
      $original = $str;

      if (preg_match ('/<a/i', $str))
        $str = preg_replace_callback ('#<a(?:rea)?[^a-z0-9>]+([^>]*?)(?:>|$)#si', array ('Security', 'jsLinkRemoval'), $str);

      if (preg_match ('/<img/i', $str))
        $str = preg_replace_callback ('#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', array ('Security', 'jsImgRemoval'), $str);

      if (preg_match ('/script|xss/i', $str))
        $str = preg_replace ('#</*(?:script|xss).*?>#si', '[removed]', $str);
    } while ($original !== $str);
    unset ($original);

    $pattern = '#' . '<((?<slash>/*\s*)((?<tagName>[a-z0-9]+)(?=[^a-z0-9]|$)|.+)' . '[^\s\042\047a-z0-9>/=]*' . '(?<attributes>(?:[\s\042\047/=]*' . '[^\s\042\047>/=]+' . '(?:\s*='  . '(?:[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*))'  . ')?'  . ')*)'  . '[^>]*)(?<closeTag>\>)?#isS';

    do {
      $old_str = $str;
      $str = preg_replace_callback ($pattern, array ('Security', 'sanitizeNaughtyHtml'), $str);
    } while ($old_str !== $str);
    unset($old_str);

    $str = preg_replace ('#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', '\\1\\2&#40;\\3&#41;', $str);

    $str = self::doNeverAllowed ($str);

    return $isImage === true ? $str === $converted_string : $str;
  }

  public static function sanitizeFilename ($str, $relative_path = false) {
    $bad = array ('../', '<!--', '-->', '<', '>', "'", '"', '&', '$', '#', '{', '}', '[', ']', '=', ';', '?', '%20', '%22', '%3c', '%253c', '%3e', '%0e', '%28', '%29', '%2528', '%26', '%24', '%3f', '%3b', '%3d');

    if (!$relative_path)
      array_push ($bad, './', '/');

    $str = remove_invisible_characters ($str, false);

    do {
      $old = $str;
      $str = str_replace ($bad, '', $str);
    } while ($old !== $str);

    return stripslashes ($str);
  }

  public static function stripImageTags ($str) {
    return preg_replace (array ('#<img[\s/]+.*?src\s*=\s*(["\'])([^\\1]+?)\\1.*?\>#i', '#<img[\s/]+.*?src\s*=\s*?(([^\s"\'=<>`]+)).*?\>#i'), '\\2', $str);
  }
}
