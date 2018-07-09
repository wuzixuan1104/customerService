<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('singular')) {
  function singular ($str) {
    if (!is_countable ($result = strval ($str)))
      return $result;

    $singular_rules = array ('/(matr)ices$/' => '\1ix', '/(vert|ind)ices$/' => '\1ex', '/^(ox)en/' => '\1', '/(alias)es$/' => '\1', '/([octop|vir])i$/' => '\1us', '/(cris|ax|test)es$/' => '\1is', '/(shoe)s$/' => '\1', '/(o)es$/' => '\1', '/(bus|campus)es$/' => '\1', '/([m|l])ice$/' => '\1ouse', '/(x|ch|ss|sh)es$/' => '\1', '/(m)ovies$/' => '\1\2ovie', '/(s)eries$/' => '\1\2eries', '/([^aeiouy]|qu)ies$/' => '\1y', '/([lr])ves$/' => '\1f', '/(tive)s$/' => '\1', '/(hive)s$/' => '\1', '/([^f])ves$/' => '\1fe', '/(^analy)ses$/' => '\1sis', '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/' => '\1\2sis', '/([ti])a$/' => '\1um', '/(p)eople$/' => '\1\2erson', '/(m)en$/' => '\1an', '/(s)tatuses$/' => '\1\2tatus', '/(c)hildren$/' => '\1\2hild', '/(n)ews$/' => '\1\2ews', '/(quiz)zes$/' => '\1', '/([^us])s$/' => '\1');

    foreach ($singular_rules as $rule => $replacement)
      if (preg_match ($rule, $result)) {
        $result = preg_replace ($rule, $replacement, $result);
        break;
      }

    return $result;
  }
}

if (!function_exists ('plural')) {
  function plural ($str) {
    if (!is_countable ($result = strval ($str)))
      return $result;

    $plural_rules = array ('/(quiz)$/' => '\1zes', '/^(ox)$/' => '\1\2en', '/([m|l])ouse$/' => '\1ice', '/(matr|vert|ind)ix|ex$/' => '\1ices', '/(x|ch|ss|sh)$/' => '\1es', '/([^aeiouy]|qu)y$/' => '\1ies', '/(hive)$/' => '\1s', '/(?:([^f])fe|([lr])f)$/' => '\1\2ves', '/sis$/' => 'ses', '/([ti])um$/' => '\1a', '/(p)erson$/' => '\1eople', '/(m)an$/' => '\1en', '/(c)hild$/' => '\1hildren', '/(buffal|tomat)o$/' => '\1\2oes', '/(bu|campu)s$/' => '\1\2ses', '/(alias|status|virus)$/' => '\1es', '/(octop)us$/' => '\1i', '/(ax|cris|test)is$/' => '\1es', '/s$/' => 's', '/$/' => 's');

    foreach ($plural_rules as $rule => $replacement)
      if (preg_match ($rule, $result)) {
        $result = preg_replace ($rule, $replacement, $result);
        break;
      }

    return $result;
  }
}

if (!function_exists ('camelize')) {
  function camelize ($str) {
    return strtolower ($str[0]) . substr (str_replace (' ', '', ucwords (preg_replace ('/[\s_]+/', ' ', $str))), 1);
  }
}

if (!function_exists ('underscore')) {
  function underscore ($str) {
    return preg_replace ('/[\s]+/', '_', trim (MB_ENABLED ? mb_strtolower ($str) : strtolower ($str)));
  }
}

if (!function_exists ('humanize')) {
  function humanize ($str, $separator = '_') {
    return ucwords (preg_replace ('/[' . preg_quote ($separator) . ']+/', ' ', trim (MB_ENABLED ? mb_strtolower ($str) : strtolower ($str))));
  }
}

if (!function_exists ('is_countable')) {
  function is_countable ($word) {
    return !in_array (strtolower ($word), array ('audio', 'bison', 'chassis', 'compensation', 'coreopsis', 'data', 'deer', 'education', 'emoji', 'equipment', 'fish', 'furniture', 'gold', 'information', 'knowledge', 'love', 'rain', 'money', 'moose', 'nutrition', 'offspring', 'plankton', 'pokemon', 'police', 'rice', 'series', 'sheep', 'species', 'swine', 'traffic', 'wheat'));
  }
}
