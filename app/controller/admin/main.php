<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class main extends AdminController {

  public function __construct () {
    parent::__construct ();
  }

  public function index () {
    $this->layout
         ->with ('current_url', URL::base ('admin'));

    return $this->view->setPath ('admin/index.php')
                ->output ();
  }
}
