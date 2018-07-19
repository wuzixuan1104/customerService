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
    $lists = $trello->get('/1/boards/' . $this->boardId . '/lists');
    if( empty($lists) )
      return false;

    $transactionLists = function ($lists, $board, &$listIds) {
      $listIds = '';
      foreach($lists as $list) {
        if( !$obj = TList::create(['board_id' => $board->id, 'key_id' => $list['id'], 'name' => $list['name']] ) )
          return false;
        $listIds .= $obj->id . ',';
      }
      $listIds = rtrim($listIds, ',');
      return true;
    };

    //label
    $labels = $trello->get('/1/boards/' . $this->boardId . '/?labels=all&label_fields=color');
    if( empty($labels) )
      return false;

    $transactionLabels = function($labels) {
      $tag = ['green' => 'deal', 'yellow' => 'new', 'orange' => 'immediate', 'red' => 'deadline', 'blue' => 'finish'];
      foreach($labels['labels'] as $label) {
        if( isset($tag[$label['color']]) && !$obj = Label::create(['key_id' => $label['id'], 'tag' => $tag[$label['color']], 'name' => $labels['labelNames'][$label['color']], 'color' => $label['color'] ] ) )
          return false;
      }
      return true;
    };


    if ($error = TList::getTransactionError ($transactionLists, $lists, $board, $listIds))
      exit('新增lists資料表錯誤');

    if ($error = Label::getTransactionError ($transactionLabels, $labels))
      exit('新增labels資料表錯誤');

    //servers
    if( !Servicer::create( array('list_ids' => $listIds, 'key_id' => '591aaa419db460a704771400') ) )
      exit('新增操作者錯誤');

    echo 'success';
  }
}
