<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Load::lib('TrelloApi.php');

class TrelloTool {

  public function __construct() {
  }

  //發送card
  public static function sendCard() {
    $source = func_get_args()[1];
    $process = json_decode($source->process, true);
    if( $process['date'] && strtotime('today') > strtotime('+1 week', strtotime($process['date'])) ) {
      $source->process = '';
      $source->save();
      return MyLineBotMsg::create()->text('此問題已超過7天未送出，無法操作此步驟');
    }

    if( !$list = TList::find_by_key_id($process['idList']) )
      return false;
    if( !$servicers = Servicer::find('all', array('where' => array('FIND_IN_SET( ?, `list_ids`)', $list->id) ) ) )
      return false;

    $param = array(
      'name' => 'From: ' . $source->title . ' ' . date('Y-m-d H:i:s'),
      'desc' => $process['content'] . "\r\n" . "---",
      'pos' => 'top',
      'due' => date( 'Y-m-d', strtotime('today + 1 week')),
      'dueComplete' => true,
      'idList' => $process['idList'],
      'idMembers' => implode(',', array_map(function($ser){ return $ser->key_id; }, $servicers)),
    );

    //新增trello card
    $trello = TrelloApi::create();
    if( !$res = $trello->request('POST', '/1/cards', $param) )
      return false;

    //將卡片存入資料庫
    $param = array(
      'list_id' => $list->id,
      'key_id' => $res->id,
      'source_id' => $source->id,
      'name' => $res->name,
      'code' => $res->shortLink,
      'status' => Card::STATUS_READY,
    );

    if( !$card = Card::create($param) )
      return false;

    //還原初始
    $source->process = '';
    $source->save();
  }
}
