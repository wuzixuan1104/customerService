<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('get_flash_params')) {
  // set $params --> $d4 === null 
  function get_flash_params ($p, $d4 = null, $cmp = null) {
    static $params;

    if ($d4 === null)
      return $params = $p;

    if (isset ($params[$p]))
      return $cmp !== null ? is_array ($params[$p]) ? in_array ($cmp, $params[$p]) : $cmp == $params[$p] : $params[$p];

    return $cmp !== null ? is_array ($d4) ? in_array ($cmp, $d4) : $cmp == $d4 : $d4;
  }
}

// if (!function_exists ('form_select')) {
//   function form_select ($name, $value, $text, $selected, $items = array ()) {
//     return '<select>' .
//              implode ('', array_map (function ($item) {
//               return '<option></option>';
//              }, $items))
//            '</select>';
//     // $input_attrs = array_filter (array ('type' => 'radio', 'name' => $name, 'value' => $value));
//     // $input_attrs = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($input_attrs), array_values ($input_attrs)));

//     // $attrs = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($attrs), array_values ($attrs)));
//     // $attrs && $attrs = ' ' . $attrs;

//     // return '<label' . $attrs . '>' .
//     //          '<input ' . $input_attrs . ($selected ? ' selected' : '') . '/>' .
//     //          '<span></span>' .
//     //          $text .
//     //        '</label>';
//   }
// }
if (!function_exists ('form_radio')) {
  function form_radio ($name, $value, $text, $checked, $attrs = array (), $attrs2 = array ()) {
    $input_attrs = array_filter (array ('type' => 'radio', 'name' => $name, 'value' => $value));
    $input_attrs = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($input_attrs), array_values ($input_attrs)));

    $attrs = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($attrs), array_values ($attrs)));
    $attrs && $attrs = ' ' . $attrs;

    $attrs2 = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($attrs2), array_values ($attrs2)));
    $attrs2 && $attrs2 = ' ' . $attrs2;

    return '<label' . $attrs . '>' .
             '<input' . $attrs2 . ' ' . $input_attrs . ($checked ? ' checked' : '') . '/>' .
             '<span></span>' .
             $text .
           '</label>';
  }
}

if (!function_exists ('form_checkbox')) {
  function form_checkbox ($name, $value, $text, $checked, $attrs = array (), $attrs2 = array ()) {
    $input_attrs = array_filter (array ('type' => 'checkbox', 'name' => $name, 'value' => $value));
    $input_attrs = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($input_attrs), array_values ($input_attrs)));

    $attrs = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($attrs), array_values ($attrs)));
    $attrs && $attrs = ' ' . $attrs;

    $attrs2 = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($attrs2), array_values ($attrs2)));
    $attrs2 && $attrs2 = ' ' . $attrs2;

    return '<label' . $attrs . '>' .
              '<input' . $attrs2 . ' ' . $input_attrs . ($checked ? ' checked' : '') . '/>' .
              '<span></span>' .
              $text .
            '</label>';
  }
}

if (!function_exists ('form_switch')) {
  function form_switch ($name, $value, $text, $checked, $attrs = array ()) {
    $input_attrs = array_filter (array ('type' => 'checkbox', 'name' => $name, 'value' => $value));
    $input_attrs = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($input_attrs), array_values ($input_attrs)));

    $attrs = implode (' ', array_map (function ($key, $value) { return $value !== null ? $key . '="' . (is_array ($value) ? json_encode ($value) : $value) . '"' : $key; }, array_keys ($attrs), array_values ($attrs)));
    $attrs && $attrs = ' ' . $attrs;

    return '<label' . $attrs . '>' .
              '<input ' . $input_attrs . ($checked ? ' checked' : '') . '/>' .
              '<span></span>' .
              $text .
            '</label>';
  }
}
