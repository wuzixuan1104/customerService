<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class cli extends Controller {
  public $boardId = '5b3f393987de8b4eae408938';

  public function index () {
    Load::lib('TrelloApi.php');
    $trello = TrelloApi::create();

    //board 目前先指定其中一版
    if( !$board = Board::create( array('key_id' => $this->boardId, 'name' => 'EC客服信箱', 'code' => 'lVs4BU5d') ) )
      return false;

    //list
    $lists = $trello->request('GET', '/1/boards/' . $this->boardId . '/lists' );
    if( empty($lists) )
      return false;

    $transactionLists = function ($lists, $board) {
      foreach($lists as $key => $list) {
        echo $key . "\r\n";
        echo "===============\r\n";
        $param = array('board_id' => $board->id, 'key_id' => $list->id, 'name' => $list->name);
        print_R($param);
        if( !$obj = Lists::create( $param ) ) {
          echo $key . ':' . 'fail';
          return false;
        }
      }
      echo 'ok';
      return true;
    };

    if ($error = Lists::getTransactionError ($transactionLists, $lists, $board))
      exit('新增lists資料表錯誤');

    echo 'success';
  }
}
