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
    Log::info(file_get_contents('php://input'));
    $data = json_decode(file_get_contents('php://input'), true);
    if( !isset($data['action']['type']) || !isset(Webhook::$typeTexts[($callType = trim($data['action']['type']))]) )
      return false;

    Load::lib('trello/Callback.php');
    Callback::create($data)->$callType();
  }
}
