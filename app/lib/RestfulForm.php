<?php

namespace Restful;

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

defined ('OACI') || exit ('此檔案不允許讀取。');

class Form {
  private $rows = array (), $hasImage = false;

  public static function create () {
    return new Form ();
  }

  public function appendFormRows () {
    foreach (func_get_args () as $row)
      $this->appendFormRow ($row);
    return $this;
  }

  public function appendFormRow (Row $row) {
    $this->hasImage |= $row instanceof Images || $row instanceof Image;
    array_push ($this->rows, $row);
    return $this;
  }
  public function setObj ($obj) {
    $this->obj = $obj;
    return $this;
  }

  public function __toString () {
    return $this->toString ();
  }

  public function toString () {
    $return = '';

    $return .= '<form class="form" action="' . ($this->obj ? \RestfulUrl::update ($this->obj) : \RestfulUrl::create ()) . '" method="post"' . ($this->hasImage ? ' enctype="multipart/form-data"' : '') . '>';
    $this->obj && $return .= '<input type="hidden" name="_method" value="put" />';

      foreach ($this->rows as $row)
        $return .= $row->setObj ($this->obj);

      $return .= '<div class="ctrl">';
        $return .= '<button type="submit">確定</button>';
        $return .= '<button type="reset">取消</button>';
      $return .= '</div>';
    $return .= '</form>';
    
    return $return;
  }
}

abstract class Row {
  protected $title, $tip, $need, $obj;

  public function __construct () {
    $this->title = null;
    $this->obj = null;
    $this->tip = '';
    $this->need = false;
  }

  public function __toString () {
    return $this->title === null ? '' : $this->toString ();
  }

  public function setObj ($obj) { $this->obj = $obj; return $this; }
  public function setTitle ($title) { $title && is_string ($title) && $this->title = $title; return $this; }
  public function setTip ($tip) { $tip && is_string ($tip) && $this->tip = $tip; return $this; }
  public function setNeed ($need) { is_bool ($need) && $this->need = $need; return $this; }
  public function getValue ($name, $value, $column = null) {
    if ($value || !$this->obj) return $value;

    $this->obj && $this->obj->{$name} !== null && $v = $this->obj->{$name};
    $v instanceof \Uploader && $v = $v->url ();
    $v instanceof ActiveRecord\DateTime && ($v = $v->format ($this instanceof \Date ? 'Y-m-d' : 'Y-m-d H:i:s'));
    is_object ($v) && $v = (string)$v;

    return $column ? array_orm_column ($v, $column) : $v;
  }

  protected static function attrs ($arr) {
    return $arr ? ' ' . implode (' ', $arr) : '';
  }
  protected function b () {
    $attrs = array ();
    $this->need && array_push ($attrs, 'class="need"');
    $this->tip  && array_push ($attrs, 'data-tip="' . $this->tip . '"');
    return '<b' . self::attrs ($attrs) . '>' . $this->title . '</b>';
  }
}

abstract class Input extends Row {
  protected $name, $value, $autofocus, $placeholder, $type, $min, $max;

  public function __construct () {
    parent::__construct ();

    $this->name = null;
    $this->value = '';
    $this->autofocus = false;
    $this->placeholder = '';
    $this->type = null;
    $this->min = 0;
    $this->max = null;
  }

  public static function create ($title, $name, $value = '') { return (new static ())->setTitle ($title)->setName ($name)->setValue ($value); }
  public static function need ($title, $name, $value = '') { return self::create ($title, $name, $value)->setNeed (true); }
  public static function maybe ($title, $name, $value = '') { return self::create ($title, $name, $value)->setNeed (false); }

  protected function setType ($type) { $type && is_string ($type) && in_array ($type, array ('text', 'color', 'number', 'date', 'email')) && $this->type = $type; return $this; }

  public function setName ($name) { $name && is_string ($name) && $this->name = $name; return $this; }
  public function setValue ($value) { (is_string ($value) || is_numeric ($value)) && $this->value = $value; return $this; }

  public function setAutofocus ($autofocus) { is_bool ($autofocus) && $this->autofocus = $autofocus; return $this; }
  public function setPlaceholder ($placeholder) { is_string ($placeholder) && $this->placeholder = $placeholder; return $this; }

  public function setMinLength ($min) { is_numeric ($min) && ($this->need ? $min > 0 : $min >= 0) && $this->min = $min; return $this; }
  public function setMaxLength ($max) { is_numeric ($max) && $max >= $this->min && $this->max = $max; return $this; }
  public function setLength ($min, $max) { return $this->setMinLength ($min)->setMaxLength ($max); }

  public function toString () {
    $return = '';

    if ($this->name === null || $this->type === null)
      return $return;
    
    $this->placeholder || $this->setPlaceholder ('請填寫「' . $this->title . '」');

    $value = get_flash_params ($this->name, '_oa_null_');
    $value = $value === '_oa_null_' ? $this->getValue ($this->name, $this->value) : $value;

    $attrs = array ('type="' . $this->type . '"', 'name="' . $this->name . '"', 'value="' . $value . '"');
    $this->need        && array_push ($attrs, 'minlength="' . $this->min . '"');
    $this->max         && array_push ($attrs, 'maxlength="' . $this->max . '"');
    $this->placeholder && array_push ($attrs, 'placeholder="' . $this->placeholder . '"');
    $this->autofocus   && array_push ($attrs, 'autofocus');
    $this->need        && array_push ($attrs, 'required');

    $return .= '<label class="row">';
      $return .= $this->b ();
      $return .= '<input' . self::attrs ($attrs) .'/>';
    $return .= '</label>';

    return $return;
  }
}
class Text extends Input {
  public function __construct () {
    parent::__construct ();
    $this->setType ('text');
  }
}
class Color extends Input {
  public function __construct () {
    parent::__construct ();
    $this->setType ('color');
  }
}
class Number extends Input {
  public function __construct () {
    parent::__construct ();
    $this->setType ('number');
  }
}
class Date extends Input {
  public function __construct () {
    parent::__construct ();
    $this->setType ('date');
  }
}
class Email extends Input {
  public function __construct () {
    parent::__construct ();
    $this->setType ('email');
  }
}

abstract class TextArea extends Row {
  protected $name, $value, $type, $autofocus, $placeholder;

  public function __construct () {
    parent::__construct ();
    $this->type = null;
    $this->name = null;
    $this->value = '';
    $this->autofocus = false;
    $this->placeholder = '';
  }

  protected function setType ($type) { $type && is_string ($type) && in_array ($type, array ('ckeditor', 'pure')) && $this->type = $type; return $this; }

  public static function create ($title, $name, $value = '') { return (new static ())->setTitle ($title)->setName ($name)->setValue ($value); }
  public static function need ($title, $name, $value = '') { return self::create ($title, $name, $value)->setNeed (true); }
  public static function maybe ($title, $name, $value = '') { return self::create ($title, $name, $value)->setNeed (false); }
  
  public function setName ($name) { $name && is_string ($name) && $this->name = $name; return $this; }
  public function setValue ($value) { (is_string ($value) || is_numeric ($value)) && $this->value = $value; return $this; }
  public function setAutofocus ($autofocus) { is_bool ($autofocus) && $this->autofocus = $autofocus; return $this; }
  public function setPlaceholder ($placeholder) { is_string ($placeholder) && $this->placeholder = $placeholder; return $this; }

  public function toString () {
    $return = '';

    if ($this->name === null || $this->type === null)
      return $return;

    $this->placeholder || $this->setPlaceholder ('請填寫「' . $this->title . '」');

    $value = get_flash_params ($this->name, '_oa_null_');
    $value = $value === '_oa_null_' ? $this->getValue ($this->name, $this->value) : $value;

    $attrs = array ('class="' . $this->type . '"', 'name="' . $this->name . '"');
    $this->placeholder && array_push ($attrs, 'placeholder="' . $this->placeholder . '"');
    $this->autofocus   && array_push ($attrs, 'autofocus');
    $this->need        && array_push ($attrs, 'required');

    if ($this instanceof PureText) {
      $this->need        && array_push ($attrs, 'minlength="' . $this->min . '"');
      $this->max         && array_push ($attrs, 'maxlength="' . $this->max . '"');
    }

    $return .= '<label class="row">';
      $return .= $this->b ();
      $return .= '<textarea' . self::attrs ($attrs) .'>' . $value . '</textarea>';
    $return .= '</label>';

    return $return;
  }
}
class PureText extends TextArea {
  protected $name, $value, $type, $autofocus, $placeholder;

  public function __construct () {
    parent::__construct ();

    $this->setType ('pure');
    $this->min = 0;
    $this->max = null;
  }

  public function setMinLength ($min) { is_numeric ($min) && ($this->need ? $min > 0 : $min >= 0) && $this->min = $min; return $this; }
  public function setMaxLength ($max) { is_numeric ($max) && $max >= $this->min && $this->max = $max; return $this; }
  public function setLength ($min, $max) { return $this->setMinLength ($min)->setMaxLength ($max); }
}
class Ckeditor extends TextArea {
  public function __construct () {
    parent::__construct ();
    $this->setType ('ckeditor');
  }
}

class Image extends Row {
  protected $name, $accept;

  public function __construct () {
    parent::__construct ();
    $this->name = null;
    $this->accept = 'image/*';
  }

  public static function create ($title, $name) { return (new static ())->setTitle ($title)->setName ($name); }
  public static function need ($title, $name) { return self::create ($title, $name)->setNeed (true); }
  public static function maybe ($title, $name) { return self::create ($title, $name)->setNeed (false); }
  
  public function setName ($name) { $name && is_string ($name) && $this->name = $name; return $this; }
  public function setAccept ($accept) { is_string ($accept) && $this->accept = $accept; return $this; }

  public function toString () {
    $return = '';

    if ($this->name === null)
      return $return;

    $value = $this->getValue ($this->name, '');

    $attrs = array ('type="file"', 'name="' . $this->name . '"');
    $this->accept && array_push ($attrs, 'accept="' . $this->accept . '"');

    $return .= '<div class="row">';
      $return .= $this->b ();
      $return .= '<div class="drop-img">';
        $return .= '<img src="' . $value . '" />';
        $return .= '<input' . self::attrs ($attrs) .'/>';
      $return .= '</div>';
    $return .= '</div>';

    return $return;
  }
}
class Images extends Row {
  protected $name, $accept, $columnName, $many;

  public function __construct () {
    parent::__construct ();
    $this->name = null;
    $this->accept = 'image/*';
    $this->columnName = null;
    $this->many = null;
  }

  public static function create ($title, $name) { return (new static ())->setTitle ($title)->setName ($name); }
  public static function need ($title, $name) { return self::create ($title, $name)->setNeed (true); }
  public static function maybe ($title, $name) { return self::create ($title, $name)->setNeed (false); }
  
  public function setName ($name) { $name && is_string ($name) && $this->name = $name; return $this; }
  public function setAccept ($accept) { is_string ($accept) && $this->accept = $accept; return $this; }
  public function setMany ($many) { is_string ($many) && $this->many = $many; return $this; }
  public function setColumnName ($columnName) { is_string ($columnName) && $this->columnName = $columnName; return $this; }

  public function toString () {
    $return = '';

    if ($this->name === null)
      return $return;

    $value = $this->obj && ($columnName = $this->columnName) && $this->obj->{$this->many} ? array_merge (array_filter (array_map (function ($t) use ($columnName) { return $t->$columnName instanceof \Uploader ? array ($t->id, $t->$columnName->url ()) : null; }, $this->obj->{$this->many})), array (array ('', ''))) : array (array ('', ''));

    $attrs2 = array ('type="hidden"', 'name="_ori_' . $this->name . '[]' . '"');
    $attrs = array ('type="file"', 'name="' . $this->name . '[]' . '"');
    $this->accept && array_push ($attrs, 'accept="' . $this->accept . '"');

    $return .= '<div class="row">';
      $return .= $this->b ();
      $return .= '<div class="multi-drop-imgs">';
      $return .= implode ('', array_map (function ($value) use ($attrs, $attrs2) {
        array_push ($attrs2, 'value="' . $value[0] . '"');

        $return = '<div class="drop-img">';
          $value[0] && $value[1] && $return .= '<input' . self::attrs ($attrs2) .'/>';
          $return .= '<img src="' . $value[1] . '" />';
          $return .= '<input' . self::attrs ($attrs) .'/>';
          $return .= '<a class="icon-04"></a>';
        $return .= '</div>';
        return $return;
      }, $value));
      $return .= '</div>';
    $return .= '</div>';

    return $return;
  }
}

class Selecter extends Row {
  protected $name, $value, $items;

  public function __construct () {
    parent::__construct ();
    $this->name = null;
    $this->value = '';
    $this->items = array ();
  }

  public static function create ($title, $name, $value = '') { return (new static ())->setTitle ($title)->setName ($name)->setValue ($value); }
  public static function need ($title, $name, $value = '') { return self::create ($title, $name, $value)->setNeed (true); }
  public static function maybe ($title, $name, $value = '') { return self::create ($title, $name, $value)->setNeed (false); }

  public function setName ($name) { $name && is_string ($name) && $this->name = $name; return $this; }
  public function setValue ($value) { (is_string ($value) || is_numeric ($value)) && $this->value = $value; return $this; }

  public function setItemKVs ($arr) { foreach ($arr as $key => $value) array_push ($this->items, array ('value' => $key, 'text' => $value)); return $this; }
  public function setItemObjs ($arr, $key = null, $val = null) { $this->items = $key !== null && $val !== null ? array_values (array_filter (array_map (function ($t) use ($key, $val) { return isset ($t->$key, $t->$val) ? array ('value' => $t->$key, 'text' => $t->$val) : null; }, $arr))) : $arr; return $this; }

  public function toString () {
    $return = '';

    if ($this->name === null)
      return $return;

    $value = get_flash_params ($this->name, '_oa_null_');
    $value = $value === '_oa_null_' ? $this->getValue ($this->name, $this->value) : $value;

    $attrs = array ('name="' . $this->name . '"');
    $this->need && array_push ($attrs, 'required');

    $return .= '<div class="row">';
      $return .= $this->b ();
      $return .= '<select' . self::attrs ($attrs) .'>';
        $return .= '<option value=""' . (get_flash_params ($this->name, $value, '') ? ' selected' : '') . '>請選擇' . $this->title . '</option>';
        foreach ($this->items as $item)
          $return .= '<option value="' . $item['value'] . '"' . (get_flash_params ($this->name, $value, $item['value']) ? ' selected' : '') . '>' . $item['text'] . '</option>';
      $return .= '</select>';
    $return .= '</div>';

    return $return;
  }
}

class Checkboxs extends Row {
  protected $name, $values, $items, $columnName, $many;

  public function __construct () {
    parent::__construct ();
    $this->name = null;
    $this->values = array ();
    $this->items = array ();
    $this->many = null;
    $this->columnName = null;
  }

  public static function create ($title, $name, $values = array ()) { return (new static ())->setTitle ($title)->setName ($name)->setValues ($values); }
  public static function need ($title, $name, $values = array ()) { return self::create ($title, $name, $values)->setNeed (true); }
  public static function maybe ($title, $name, $values = array ()) { return self::create ($title, $name, $values)->setNeed (false); }

  public function setName ($name) { $name && is_string ($name) && $this->name = $name; return $this; }
  public function setValues ($values) { is_array ($values) && $this->values = $values; return $this; }
  public function setMany ($many) { is_string ($many) && $this->many = $many; return $this; }
  public function setColumnName ($columnName) { is_string ($columnName) && $this->columnName = $columnName; return $this; }
  
  public function setItemKVs ($arr) { foreach ($arr as $key => $value) array_push ($this->items, array ('value' => $key, 'text' => $value)); return $this; }
  public function setItemObjs ($arr, $key = null, $val = null) { $this->items = $key !== null && $val !== null ? array_values (array_filter (array_map (function ($t) use ($key, $val) { return isset ($t->$key, $t->$val) ? array ('value' => $t->$key, 'text' => $t->$val) : null; }, $arr))) : $arr; return $this; }

  public function toString () {
    $return = '';

    if ($this->name === null)
      return $return;

    if (!$this->items)
      return $return;

    $values = get_flash_params ($this->name . '[]', '_oa_null_');
    $values = $values === '_oa_null_' ? $this->getValue ($this->many, $this->values, $this->columnName) : $values;

    $attrs = array ('name="' . $this->name . '"');
    $this->need && array_push ($attrs, 'required');

    $return .= '<div class="row">';
      $return .= $this->b ();
      $return .= '<div class="checkboxs">';

      foreach ($this->items as $item)
        $return .= form_checkbox ($this->name . '[]', $item['value'], $item['text'], $values && in_array ($item['value'], $values));

      $return .= '</div>';
    $return .= '</div>';

    return $return;
  }
}

class Radior extends Row {
  protected $name, $value, $items;

  public function __construct () {
    parent::__construct ();
    $this->name = null;
    $this->value = null;
    $this->items = array ();
  }
  
  public static function create ($title, $name, $value = null) { return (new static ())->setTitle ($title)->setName ($name)->setValue ($value); }
  public static function need ($title, $name, $value = null) { return self::create ($title, $name, $value)->setNeed (true); }
  public static function maybe ($title, $name, $value = null) { return self::create ($title, $name, $value)->setNeed (false); }

  public function setName ($name) { $name && is_string ($name) && $this->name = $name; return $this; }
  public function setValue ($value) { (is_string ($value) || is_numeric ($value)) && $this->value = $value; return $this; }

  public function setItemKVs ($arr) { foreach ($arr as $key => $value) array_push ($this->items, array ('value' => $key, 'text' => $value)); return $this; }
  public function setItemObjs ($arr, $key = null, $val = null) { $this->items = $key !== null && $val !== null ? array_values (array_filter (array_map (function ($t) use ($key, $val) { return isset ($t->$key, $t->$val) ? array ('value' => $t->$key, 'text' => $t->$val) : null; }, $arr))) : $arr; return $this; }

  public function toString () {
    $return = '';

    if ($this->name === null)
      return $return;

    if (!$this->items)
      return $return;

    $value = get_flash_params ($this->name, '_oa_null_');
    $value = $value === '_oa_null_' ? $this->getValue ($this->name, $this->value) : $value;
    
    $return .= '<div class="row">';
      $return .= $this->b ();
      $return .= '<div class="radios">';
        foreach ($this->items as $item)
          $return .= form_radio ($this->name, $item['value'], $item['text'], $value !== null && $value == $item['value'], array (), $this->need ? array ('required' => null) : array ());
      $return .= '</div>';
    $return .= '</div>';

    return $return;
  }
}

class Switcher extends Row {
  protected $name, $value, $checkedValue;

  public function __construct () {
    parent::__construct ();
    $this->name = null;
    $this->value = null;
    $this->checkedValue = null;
  }
  
  public static function create ($title, $name, $value = null) { return (new static ())->setTitle ($title)->setName ($name)->setValue ($value); }
  public static function need ($title, $name, $value = null) { return self::create ($title, $name, $value)->setNeed (true); }
  public static function maybe ($title, $name, $value = null) { return self::create ($title, $name, $value)->setNeed (false); }

  public function setName ($name) { $name && is_string ($name) && $this->name = $name; return $this; }
  public function setValue ($value) { (is_string ($value) || is_numeric ($value)) && $this->value = $value; return $this; }
  public function setCheckedValue ($value) { (is_string ($value) || is_numeric ($value)) && $this->checkedValue = $value; return $this; }

  public function toString () {
    $return = '';

    if ($this->name === null)
      return $return;

    if ($this->checkedValue === null)
      return $return;

    $value = get_flash_params ($this->name, '_oa_null_');
    $value = $value === '_oa_null_' ? $this->getValue ($this->name, $this->value) : $value;

    $return .= '<div class="row min">';
      $return .= $this->b () . ' ';
      $return .= '<div class="switches">';
      $return .= form_switch ($this->name, $this->checkedValue, '', $value === $this->checkedValue);
      $return .= '</div>';
    $return .= '</div>';

    return $return;
  }
}

class LatLng extends Row {
  protected $lat_name, $lng_name, $lat_value, $lng_value;

  public function __construct () {
    parent::__construct ();
    $this->lat_name = null;
    $this->lng_name = null;
    $this->lat_value = null;
    $this->lng_value = null;
  }
  
  public static function create ($title, $lat_name, $lng_name, $lat_value = null, $lng_value = null) { return (new static ())->setTitle ($title)->setLatName ($lat_name)->setLngName ($lng_name)->setLatValue ($lat_value)->setLngValue ($lng_value); }
  public static function need ($title, $lat_name, $lng_name, $lat_value = null, $lng_value = null) { return self::create ($title, $lat_name, $lng_name, $lat_value, $lng_value)->setNeed (true); }
  public static function maybe ($title, $lat_name, $lng_name, $lat_value = null, $lng_value = null) { return self::create ($title, $lat_name, $lng_name, $lat_value, $lng_value)->setNeed (false); }

  public function setLatName ($name) { $name && is_string ($name) && $this->lat_name = $name; return $this; }
  public function setLatValue ($value) { is_numeric ($value) &&  $value > -85 && $value < 85 && $this->lat_value = $value; return $this; }
  
  public function setLngName ($name) { $name && is_string ($name) && $this->lng_name = $name; return $this; }
  public function setLngValue ($value) { is_numeric ($value) && $value > -180 && $value < 180 && $this->lng_value = $value; return $this; }

  public function toString () {
    $return = '';

    if ($this->lat_name === null || $this->lng_name === null)
      return $return;

    $lat_value = get_flash_params ($this->lat_name, '_oa_null_');
    $lat_value = $lat_value === '_oa_null_' ? $this->getValue ($this->lat_name, $this->lat_value) : $lat_value;

    $lng_value = get_flash_params ($this->lng_name, '_oa_null_');
    $lng_value = $lng_value === '_oa_null_' ? $this->getValue ($this->lng_name, $this->lng_value) : $lng_value;

    $lat_attrs = array ('type="number"', 'name="' . $this->lat_name . '"', 'value="' . $lat_value . '"');
    $lng_attrs = array ('type="number"', 'name="' . $this->lng_name . '"', 'value="' . $lng_value . '"');

    $return .= '<div class="row">';
      $return .= $this->b ();

      $return .= '<div class="map-edit">';
        $return .= '<input' . self::attrs ($lat_attrs) .'/>';
        $return .= '<input' . self::attrs ($lng_attrs) .'/>';
      $return .= '</div>';
    $return .= '</div>';

    return $return;
  }
}














// class LatLng extends Row {
//   private $latName = 'latitude';
//   private $lngName = 'longitude';
//   private $latValue = 23.79539759;
//   private $lngValue = 120.88256835;
//   private $step = 'any';

//   public function setLat ($name, $value = '') {
//     $this->latName = $name;
//     $value > -85 && $value < 85 && $this->latValue = $value;
//     return $this;
//   }
//   public function setLng ($name, $value = '') {
//     $this->lngName = $name;
//     $value > -180 && $value < 180 && $this->lngValue = $value;
//     return $this;
//   }
//   public function setStep ($step) {
//     $step && is_numeric ($step) && $this->step = $step;
//     return $this;
//   }
//   public function toString () {
//     $return = '';

//     if (!($this->title && $this->latName && $this->lngName))
//       return $return;

//     $return .= '<div class="row">';
//       $return .= '<b' . ($this->need ? ' class="need"' : '') .'' . ($this->tip ? ' data-tip="' . $this->tip . '"' : '') . '>' . $this->title . '</b>';
//       $return .= '<div class="map-edit">';
//         $return .= '<input type="number" name="' . $this->latName . '" step="' . $this->step . '" value="' . get_flash_params ($this->latName, $this->latValue) . '" />';
//         $return .= '<input type="number" name="' . $this->lngName . '" step="' . $this->step . '" value="' . get_flash_params ($this->lngName, $this->lngValue) . '" />';
//       $return .= '</div>';
//     $return .= '</div>';

//     return $return;
//   }
// }

// class Multi extends Row {
//   protected $name = '', $value = array (), $columns = array ();

//   // public function __construct ($title = '') {
//   //   parent::__construct ($title);
//   // }

//   public function setColumns () {
//     $this->columns = array_filter (func_get_args (), function ($t) { return in_array (get_class ($t), array (
//       'AdminLib\Form\Row\Multi\Text',
//       'AdminLib\Form\Row\Multi\Select',
//       'AdminLib\Form\Row\Multi\Checkboxs')); });
//     return $this;
//   }

//   public function setName ($name) {
//     $this->name = $name;
//     return $this;
//   }

//   public function setValue ($value) {
//     is_array ($this->value) && $this->value = $value;
//     return $this;
//   }
//   public function toString () {
//     $return = '';

//     $names = array_map (function ($t) { return $t->getName(); }, $this->columns);

//     $flash = get_flash_params ('sources', false);
//     $datas = $flash !== false ? $flash : $this->value;
//     $datas = array_values (array_filter (array_map (function ($data) use ($names) { $nData = array (); foreach ($names as $name) if (isset ($data[$name])) $nData[$name] = $data[$name]; else return null; return $nData; }, $datas)));
//     array_unshift ($datas, '');


//     if (!($this->title))
//       return $return;

//     $return .= '<div class="row">';
//       $return .= '<b' . ($this->need ? ' class="need"' : '') .'' . ($this->tip ? ' data-tip="' . $this->tip . '"' : '') . '>' . $this->title . '</b>';
//       $return .= '<div class="multi-datas" data-index="' . (count ($datas) - 1) . '">';

//         foreach ($datas as $i => $data) {
//           $return .= '<div class="datas' . ($data === '' ? ' demo' : '') . '">';

//           foreach ($this->columns as $column)
//             $return .= $data === '' ? $column->isDemo (true)->setValue ('')->setPrefix ($this->name) : $column->isDemo (false)->setValue ($data[$column->getName ()])->setPrefix ($this->name . '[' . ($i - 1) . ']');

//             $return .= '<a class="icon-04 del"></a>';
//           $return .= '</div>';
//         }

//         $return .= '<div class="btns">';
//           $return .= '<a class="icon-07 add">參考鏈結</a>';
//         $return .= '</div>';
//       $return .= '</div>';
//     $return .= '</div>';

//     return $return;
//   }
// }
