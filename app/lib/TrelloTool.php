<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Load::lib ('MyLineBot.php');

class TrelloTool {

  public function __construct() {
  }

  //發送card
  public static function sendCard() {
    $source = func_get_args()[1];
    print_R($source);
    die;
  }
}
