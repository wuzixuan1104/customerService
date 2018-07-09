<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (is_php ('5.5'))
  return;

if (!function_exists ('array_column')) {
  function array_column (array $array, $column_key, $index_key = null) {
    if (!in_array ($type = gettype ($column_key), array ('integer', 'string', 'null'), true)) {
      if ($type === 'double') {
        $column_key = (int) $column_key;
      } else if ($type === 'object' && method_exists ($column_key, '__toString')) {
        $column_key = (string) $column_key;
      } else {
        trigger_error ('array_column (): The column key should be either a string or an integer', E_USER_WARNING);
        return false;
      }
    }

    if (!in_array ($type = gettype ($index_key), array ('integer', 'string', 'null'), true)) {
      if ($type === 'double') {
        $index_key = (int) $index_key;
      } else if ($type === 'object' && method_exists ($index_key, '__toString')) {
        $index_key = (string) $index_key;
      } else {
        trigger_error ('array_column (): The index key should be either a string or an integer', E_USER_WARNING);
        return false;
      }
    }

    $result = array ();
    foreach ($array as &$a) {
      if ($column_key === null)
        $value = $a;
      else if (is_array ($a) && array_key_exists ($column_key, $a))
        $value = $a[$column_key];
      else
        continue;

      if ($index_key === null || !array_key_exists ($index_key, $a))
        $result[] = $value;
      else
        $result[$a[$index_key]] = $value;
    }

    return $result;
  }
}

if (is_php ('5.4'))
  return;

if (!function_exists ('hex2bin')) {
  function hex2bin ($data) {
    if (in_array ($type = gettype ($data), array ('array', 'double', 'object', 'resource'), true)) {
      if ($type === 'object' && method_exists ($data, '__toString')) {
        $data = (string) $data;
      } else {
        trigger_error ('hex2bin () expects parameter 1 to be string, ' . $type . ' given', E_USER_WARNING);
        return null;
      }
    }

    if (strlen ($data) % 2 !== 0) {
      trigger_error ('Hexadecimal input string must have an even length', E_USER_WARNING);
      return false;
    } else if (!preg_match ('/^[0-9a-f]*$/i', $data)) {
      trigger_error ('Input string must be hexadecimal string', E_USER_WARNING);
      return false;
    }

    return pack ('H*', $data);
  }
}
