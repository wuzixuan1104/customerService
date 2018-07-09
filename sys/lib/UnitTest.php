<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class UnitTest {
  private $results = array ();
  private $strict = false;
  private $_template = null;
  private $_template_rows = null;
  private $_test_items_visible  = array ('測試名稱', '資料格式', '預期格式', '結果', '檔案名稱', '行數', '備註');

  public function __construct () {}

  public function run ($test, $expected = true, $name = 'undefined', $notes = '') {
    if (in_array ($expected, array ('is_object', 'is_string', 'is_bool', 'is_true', 'is_false', 'is_int', 'is_numeric', 'is_float', 'is_double', 'is_array', 'is_null', 'is_resource'), true)) {
      $result = $expected ($test);
      $extype = str_replace (array ('true', 'false'), 'bool', str_replace ('is_', '', $expected));
    } else {
      $result = ($this->strict === true) ? ($test === $expected) : ($test == $expected);
      $extype = gettype ($expected);
    }

    $back = $this->_backtrace ();

    $report = array (
      '測試名稱' => $name,
      '資料格式' => gettype ($test),
      '預期格式' => $extype,
      '結果' => $result === true,
      '檔案名稱' => $back['file'],
      '行數' => $back['line'],
      '備註' => $notes
    );

    array_push ($this->results, $report);

    return $this;
  }

  public function report () {
    $this->_parse_template ();

    $r = '';
    foreach ($this->results as $results) {
      $table = '';

      foreach ($results as $key => $result) {
        if ($key === '結果')
          $result = $result ? '<span style="color: #0C0;">完成</span>' : '<span style="color: #C00;">失敗</span>';

        $table .= str_replace (array ('{item}', '{result}'), array ($key, $result), $this->_template_rows);
      }

      $r .= str_replace ('{rows}', $table, $this->_template);
    }

    return $r;
  }

  public function setStrict ($state = true) {
    $this->strict = (bool) $state;
    return $this;
  }


  public function result ($results = array ()) {
    if (!$results)
      $results = $this->results;

    return array_map (function ($result) {
      $temp = array ();

      foreach ($result as $key => $val)
        if (!isset ($this->_test_items_visible[$key]))
          continue;
        else
          $temp[$this->_test_items_visible[$key]] = $val;

      return $temp;
    }, $results);
  }

  public function set_template ($template) {
    $this->_template = $template;
  }

  protected function _backtrace () {
    $back = debug_backtrace ();
    return array ('file' => (isset ($back[1]['file']) ? $back[1]['file'] : ''), 'line' => (isset ($back[1]['line']) ? $back[1]['line'] : ''));
  }

  protected function _default_template () {
    $this->_template = "\n" . '<table style="width:100%; font-size:small; margin:10px 0; border-collapse:collapse; border:1px solid #CCC;">{rows}' . "\n</table>";
    $this->_template_rows = "\n\t<tr>\n\t\t" . '<th style="text-align: left; border-bottom:1px solid #CCC;">{item}</th>' . "\n\t\t" . '<td style="border-bottom:1px solid #CCC;">{result}</td>' . "\n\t</tr>";
  }

  protected function _parse_template () {
    if ($this->_template_rows)
      return;

    if ($this->_template === null || !preg_match ('/\{rows\}(.*?)\{\/rows\}/si', $this->_template, $match)) {
      $this->_default_template ();
      return;
    }

    $this->_template_rows = $match[1];
    $this->_template = str_replace ($match[0], '{rows}', $this->_template);
  }
}

function is_true ($test) {
  return $test === true;
}

function is_false($test) {
  return $test === false;
}
