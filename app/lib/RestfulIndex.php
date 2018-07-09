<?php

namespace Restful;

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

defined ('OACI') || exit ('此檔案不允許讀取。');

class Order {
  const KEY = '_o';
  const SPLIT_KEY = ':';

  private $sort = 'id DESC';

  public function __construct ($sort = '') {
    if ($sort && count ($sort = array_values (array_filter (array_map ('trim', explode (' ', $sort))))) == 2 && in_array (strtolower ($sort[1]), array ('desc', 'asc')))
      $this->sort = $sort[0] . ' ' . strtoupper ($sort[1]);

    if (($sort = \Input::get (Order::KEY)) && count ($sort = array_values (array_filter (array_map ('trim', explode (Order::SPLIT_KEY, $sort))))) == 2 && in_array (strtolower ($sort[1]), array ('desc', 'asc')))
      $this->sort = $sort[0] . ' ' . strtoupper ($sort[1]);
  }
  
  public static function set ($title, $column = '') {
    if (!$column) return $title;

    $gets = \Input::get ();
    
    if (!(isset ($gets[Order::KEY]) && count ($sort = array_values (array_filter (explode (Order::SPLIT_KEY, $gets[Order::KEY])))) == 2 && in_array (strtolower ($sort[1]), array ('desc', 'asc')) && ($sort[0] == $column))) {
      $gets[Order::KEY] = $column . Order::SPLIT_KEY . 'desc';
      return $title . ' <a href="' . \URL::current () . '?' . http_build_query ($gets) . '" class="sort"></a>';
    }
    $class = strtolower ($sort[1]);
    if ($class != 'asc')
      $gets[Order::KEY] = $column . Order::SPLIT_KEY . 'asc';
    else
      unset ($gets[Order::KEY]);

    return $title . ' <a href="' . \URL::current () . '?' . http_build_query ($gets) . '" class="sort ' . $class . '"></a>';
  }

  private static function _desc ($column = '') {
    return ($column ? $column : 'id') . ' ' . strtoupper ('desc');
  }

  private static function _asc ($column = '') {
    return ($column ? $column : 'id') . ' ' . strtoupper ('asc');
  }

  public function __call ($name, $arguments) {
    switch (strtolower (trim ($name))) {
      case 'asc':
        $this->sort = call_user_func_array (array ('self', '_asc'), $arguments);
        break;

      case 'desc':
        $this->sort = call_user_func_array (array ('self', '_desc'), $arguments);
        break;

      default:
        gg ('AdminLib\Order 沒有「' . $name . '」方法。');
        break;
    }
    return $this;
  }

  public static function __callStatic ($name, $arguments) {
    switch (strtolower (trim ($name))) {
      case 'asc':
        return Order::create (call_user_func_array (array ('self', '_asc'), $arguments));
        break;

      case 'desc':
        return Order::create (call_user_func_array (array ('self', '_desc'), $arguments));
        break;

      default:
        gg ('AdminLib\Order 沒有「' . $name . '」方法。');
        break;
    }
  }

  public function __toString () {
    return $this->toString ();
  }

  public function toString () {
    return $this->sort;
  }

  public static function create ($sort = '') {
    return new Order ($sort);
  }
}

class Search {
  const KEY = '_q';

  private $titles = array ();
  private $counter = 0;
  private $where = array ();
  private $searches = array ();

  private $objs = array ();
  private $total = 0;
  private $addUrl = '';
  private $sortUrl = '';
  private $table = null;
  
  public function __construct (&$where = null) {
    $where !== null || $where = Where::create ();

    $this->where = $where;
    $this->counter = 0;
    $this->titles = array ();
    $this->searches = array ();

    $this->total = 0;
    $this->addUrl = '';
    $this->sortUrl = '';
    $this->objs = array ();
    $this->table = Table::create ();
  }

  private function add ($key) {
    $value = \Input::get ($key, true);

    if ($value === null || $value === '' || (is_array ($value) && !count ($value)) || empty ($this->searches[$key]['sql']))
      return $this;
    
    is_callable ($this->searches[$key]['sql']) && $this->where->and ($this->searches[$key]['sql']($value));
    is_string ($this->searches[$key]['sql']) && $this->where->and ($this->searches[$key]['sql'], strpos (strtolower ($this->searches[$key]['sql']), ' like ') !== false ? '%' . $value . '%' : $value);
    is_object ($this->searches[$key]['sql']) && $this->searches[$key]['sql'] instanceof Where && $this->where->and ($this->searches[$key]['sql']);

    $this->searches[$key]['value'] = $value;
    array_push ($this->titles, $this->searches[$key]['title']);
    return $this;
  }
  public function input ($title, $sql, $type = 'text') {
    $this->searches[$key = Search::KEY . ($this->counter++)] = array ('el' => 'input', 'title' => $title, 'sql' => $sql, 'type' => $type);
    return $this->add ($key);
  }

  public function select ($title, $sql, $options) {
    $this->searches[$key = Search::KEY . ($this->counter++)] = array ('el' => 'select', 'title' => $title, 'sql' => $sql, 'options' => $options);
    return $this->add ($key);
  }
  
  public function checkboxs ($title, $sql, $items) {
    $this->searches[$key = Search::KEY . ($this->counter++)] = array ('el' => 'checkboxs', 'title' => $title, 'sql' => $sql, 'items' => $items);
    return $this->add ($key);
  }
  
  public function radios ($title, $sql, $items) {
    $this->searches[$key = Search::KEY . ($this->counter++)] = array ('el' => 'radios', 'title' => $title, 'sql' => $sql, 'items' => $items);
    return $this->add ($key);
  }
  
  private function conditions () {
    $gets = \Input::get ();

    $return = '<div class="conditions">';
      
      $return .= implode ('', array_map (function ($key, $condition) use (&$gets) {
        unset ($gets[$key]);
        $return = '';

        if (!(isset ($condition['el']) && in_array ($condition['el'], array ('input', 'select', 'checkboxs', 'radios'))))
          return $return;

        switch ($condition['el']) {
          case 'input':
            if (!isset ($condition['title']))
              return $return;

            $return .= '<label class="row">';
            $return .= '<b>依據' . $condition['title'] . '搜尋</b>';
            $return .= '<input name="' . $key . '" type="' . (isset ($condition['type']) ? $condition['type'] : 'text') . '" placeholder="依據' . $condition['title'] . '搜尋" value="' . (empty ($condition['value']) ? '' : $condition['value']) . '" />';
            $return .= '</label>';
            break;
          
          case 'select':
            if (!isset ($condition['title'], $condition['options']))
              return $return;

            $return .= '<label class="row">';
            $return .= '<b>依據' . $condition['title'] . '搜尋</b>';
            $return .= '<select name="' . $key . '">';
            $return .= '<option value="">依據' . $condition['title'] . '搜尋</option>';
            $return .= implode ('', array_map (function ($option) use ($condition) { return $option && isset ($option['value'], $option['text']) ? '<option value="' . $option['value'] . '"' . (!empty ($condition['value']) && $condition['value'] == $option['value'] ? ' selected' : '') . '>' . $option['text'] . '</option>' : ''; }, $condition['options']));
            $return .= '</select>';
            $return .= '</label>';
            break;
          
          case 'checkboxs':
            if (!isset ($condition['title'], $condition['items']))
              return $return;

            $return .= '<div class="row">';
            $return .= '<b>依據' . $condition['title'] . '搜尋</b>';
            $return .= '<div class="checkboxs">';
            $return .= implode ('', array_map (function ($option) use ($condition, $key) { return $option && isset ($option['value'], $option['text']) ? '<label><input type="checkbox" name="' . $key . '[]" value="' . $option['value'] . '"' . (!empty ($condition['value']) && (is_array ($condition['value']) ? in_array ($option['value'], $condition['value']) : $condition['value'] == $option['value']) ? ' checked' : '') . ' /><span></span>' . $option['text'] . '</label>' : ''; }, $condition['items']));
            $return .= '</div>';
            $return .= '</div>';
            break;

          case 'radios':
            if (!isset ($condition['title'], $condition['items']))
              return $return;

            $return .= '<div class="row">';
            $return .= '<b>依據' . $condition['title'] . '搜尋</b>';
            $return .= '<div class="radios">';
            $return .= implode ('', array_map (function ($option) use ($condition, $key) { return $option && isset ($option['value'], $option['text']) ? '<label><input type="radio" name="' . $key . '" value="' . $option['value'] . '"' . (!empty ($condition['value']) && $condition['value'] == $option['value'] ? ' checked' : '') . ' /><span></span>' . $option['text'] . '</label>' : ''; }, $condition['items']));
            $return .= '</div>';
            $return .= '</div>';
            break;
          
          default:
            return $return;
            break;
        }

        return $return;
      }, array_keys ($this->searches), array_values ($this->searches)));

      $gets = http_build_query ($gets);
      $gets && $gets = '?' . $gets;

      $return .= '<div class="btns">';
        $return .= '<button type="submit">搜尋</button>';
        $return .= '<a href="' . \URL::current () . $gets . '">取消</a>';
      $return .= '</div>';
    $return .= '</div>';

    return $return;
  }
  public function setSortUrl ($sortUrl) {
    $this->sortUrl = $sortUrl;
    $this->table->setSortUrl ($this->sortUrl);

    return $this;
  }
  public function setAddUrl ($addUrl) {
    $this->addUrl = $addUrl;
    return $this;
  }
  public function setTotal ($total) {
    $this->total = $total;
    return $this;
  }
  public function setObjs (array $objs) {
    $this->objs = $objs;
    $this->table || $this->table = Table::create ();
    $this->table->setObjs ($this->objs);
    return $this;
  }
  public function setTable (Table $table) {
    $this->table = $table;
    $this->sortUrl && $this->table->setSortUrl ($this->sortUrl);
    return $this;
  }
  public function setTableClomuns () {
    $args = func_get_args ();
    foreach ($args as $arg)
      $this->table->appendClomun ($arg);
    return $this->table;
  }
  public function getTable () {
    return $this->table;
  }
  public function __toString () {
    return $this->toString ();
  }

  public function toString () {
    $sortKey = '';

    if ($this->table->isUseSort ()) {
      $gets = \Input::get ();

      if (isset ($gets[Order::KEY]))
        unset ($gets[Order::KEY]);

      foreach (array_keys ($this->searches) as $key)
        if (isset ($gets[$key]))
          unset ($gets[$key]);
  
      if (isset ($gets[Table::KEY]) && $gets[Table::KEY] === 'true') {
        $ing = false;
        unset ($gets[Table::KEY]);
      } else {
        $ing = true;
        $gets[Table::KEY] = 'true';
      }

      $gets = http_build_query ($gets);
      $gets && $gets = '?' . $gets;
      $sortKey = \URL::current () . $gets;
    }

    $return = '<form class="search" action="' . \RestfulUrl::index () . '" method="get">';
      $return .= '<div class="info' . ($this->titles ? ' show' : '') . '">';
        $return .= '<a class="icon-13 conditions-btn"></a>';

        $return .= '<span>' . ($this->addUrl ? '<a href="' . $this->addUrl . '" class="icon-07">新增</a>' : '') . ($sortKey ? '<a href="' . $sortKey . '" class="icon-' . ($ing ? '41' : '18') . '">' . ($ing ? '排序' : '完成') . '</a>' : '') . '</span>';
        $return .= '<span>' . ($this->titles ? '您針對' . implode ('、', array_map (function ($title) { return '「' . $title . '」'; }, $this->titles)) . '搜尋，結果' : '目前全部') . '共有 <b>' . number_format ($this->total) . '</b> 筆。' . '</span>';
      $return .= '</div>';
      $return .= $this->conditions ();
    $return .= '</form>';
    return $return;
  }

  public static function create (&$where = null) {
    return new Search ($where);
  }
}

class Column {
  protected $sort, $class, $width, $title;
  
  public function __construct ($title = '') {
    $this->setTitle ($title)
         ->setSort ('');
  }

  public function setTd ($td) {
    $this->td = $td;
    return $this;
  }

  public function setSort ($sort) {
    $this->sort = $sort;
    return $this;
  }

  public function setClass ($class) {
    $this->class = $class;
    return $this;
  }

  public function setWidth ($width) {
    is_numeric ($width) && $this->width = $width;
    return $this;
  }

  public function setSwitch ($checked, $attrs = array ()) {
    return form_switch ('', '', '', $checked, $attrs);
  }

  public function setTitle ($title) {
    $this->title = $title;
    return $this;
  }

  public function thString ($sortUrl = '') {
    return '<th' . ($this->width ? ' width="' . $this->width . '"' : '') . '' . ($this->class ? ' class="' . $this->class . '"' : '') . '>' . Order::set ($this->title, $sortUrl ? '' : $this->sort) . '</th>';
  }

  public function tdString ($obj) {
    $td = $this->td;
    
    if (is_string ($td))
      $text = $td;

    if (is_callable ($td))
      $text = $td ($obj, $this);

    if (is_object ($text) && $text instanceof EditColumn && method_exists ($text, '__toString'))
      $text = (string)$text;

    return '<td' . ($this->width ? ' width="' . $this->width . '"' : '') . '' . ($this->class ? ' class="' . $this->class . '"' : '') . '>' . $text . '</td>';
  }

  public static function create ($title = '') {
    return new Column ($title);
  }
}

class EditColumn extends Column {
  protected $links = array ();

  public function __construct ($title = '') {
    parent::__construct ($title);
    $this->setWidth (78)
         ->setClass ('edit');
  }

  public function addEditLink ($url) {
    return $this->addLink ($url, array ('class' =>'icon-03'));
  }

  public function addDeleteLink ($url) {
    return $this->addLink ($url, array ('class' =>'icon-04', 'data-method' =>'delete'));
  }

  public function addShowLink ($url) {
    return $this->addLink ($url, array ('class' =>'icon-29'));
  }

  public function addLink ($url, $attrs = array ()) {
    $attrs['href'] = $url;
    array_push ($this->links, $attrs);
    return $this;
  }

  public function __toString () {
    return $this->toString ();
  }

  public function toString () {
    return implode ('', array_filter (array_map (function ($link) {
      $attr = implode (' ', array_map (function ($key, $val) { return $key . '="' . $val . '"'; }, array_keys ($link), array_values ($link)));
      return $attr ? '<a ' . $attr . '></a>' : null;
    }, $this->links)));
  }

  public static function create ($title = '') {
    return new EditColumn ($title);
  }
}

class Table {
  const KEY = '_s';
  private $objs, $columns, $sortUrl, $useSort = false;
  
  public function __construct ($objs = array ()) {
    $this->setObjs ($objs);
    $this->columns = array ();
    $this->sortUrl = '';
  }

  public function prependClomun (Column $column) {
    array_unshift ($this->columns, $column);
    return $this;
  }
  public function appendClomun (Column $column) {
    array_push ($this->columns, $column);
    return $this;
  }

  public function isUseSort () {
    return $this->useSort;
  }
  public function setSortUrl ($url) {
    $this->useSort = true;

    if ($url && ($get = \Input::get (Table::KEY)) === 'true' && ($this->sortUrl = $url))
      $this->prependClomun (Column::create ('排序')->setWidth (44)->setClass ('center')->setTd ('<span class="icon-01 drag"></span>'));
    return $this;
  }
  public function setObjs ($objs) {
    $this->objs = $objs;
    return $this;
  }

  public static function create () {
    if (!$args = func_get_args ())
      return new Table ();

    $instance = is_array ($objs = array_shift ($args)) ? new Table ($objs) : new Table ();
    
    foreach ($args as $arg)
      $instance->appendClomun ($arg);

    return $instance;
  }

  public function __toString () {
    return $this->toString ();
  }

  public function toString () {
    $return = '';
    $sortUrl = $this->sortUrl;

    if ($sortUrl)
      $return .= '<table class="list dragable" data-sorturl="' . $sortUrl . '">';
    else
      $return .= '<table class="list">';

      $return .= '<thead>';
        $return .= '<tr>';
        $return .= implode ('', array_map (function ($column) use ($sortUrl) { return $column->thString ($sortUrl); }, $this->columns));
        $return .= '</tr>';
      $return .= '</thead>';
      $return .= '<tbody>';
      // 
      if (!$this->objs)
        $return .= '<tr><td colspan="' . count ($this->columns) . '"></td></tr>';
      else
        $return .= implode ('', array_map (function ($obj) use ($sortUrl) {
          return ($sortUrl && isset ($obj->id, $obj->sort) ? '<tr data-id="' . $obj->id . '" data-sort="' . $obj->sort . '">' : '<tr>') . implode ('', array_map (function ($column) use ($obj) { 
            $column = clone $column;
            return $column->tdString ($obj); }, $this->columns)) . '</tr>';
        }, $this->objs));


      $return .= '</tbody>';
    $return .= '</table>';

    return $return;
  }
}

