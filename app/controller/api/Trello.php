<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Trello extends ApiController {
  public function __construct() {
    parent::__construct();
  }

  public function callback() {
    $json = file_get_contents('php://input');
    $action = json_decode($json,true);
    var_dump($action);
  }
}
