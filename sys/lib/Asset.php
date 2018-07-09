<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Asset {
  // private $instance;
  private $version, $list;
  
  public function __construct ($version = 0) {
    $this->version = $version;
  }

  public static function create ($version) {
    return new Asset ($version);
  }

  public function getList ($type = null) {
    return isset ($this->list[$type]) ? $this->list[$type] : $this->list;
  }

  public function addList ($type, $path, $minify = true) {
    is_string ($path) || $path = '/' . ltrim (preg_replace ('/\/+/', '/', implode ('/', array_2d_to_1d ($path))), '/');
    isset ($list[$type]) || $list[$type] = array ();
    
    preg_match ('/^https?:\/\/.*/', $path) && $minify = false;
    $this->list[$type][$path] = $minify;
    return $this;
  }

  public function addJS ($path, $minify = true) {
    return $this->addList ('js', $path, $minify);
  }

  public function addCSS ($path, $minify = true) {
    return $this->addList ('css', $path, $minify);
  }

  public function render () {

  }
  public function renderCSS () {
    $str = '';

    if (empty ($this->list['css']))
      return $str;

    foreach ($this->list['css'] as $path => $minify)
      $str .= '<link href="' . asset ($path) . '?v=' . $this->version . '" rel="stylesheet" type="text/css" />';

    return $str;
  }

  public function renderJS () {
    $str = '';

    if (empty ($this->list['js']))
      return $str;

    foreach ($this->list['js'] as $path => $minify)
      $str .= '<script src="' . asset ($path) . '?v=' . $this->version . '" language="javascript" type="text/javascript" ></script>';

    return $str;
  }
}