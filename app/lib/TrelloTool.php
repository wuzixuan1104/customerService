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
      return MyLineBotMsg::create()->text('系統發生問題');
    if( !$servicers = Servicer::find('all', array('where' => array('FIND_IN_SET( ?, `list_ids`)', $list->id) ) ) )
      return MyLineBotMsg::create()->text('系統發生問題');

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
    // if( !$res = $trello->request('POST', '/1/cards', $param) )
    //   return MyLineBotMsg::create()->text('無法傳送trello卡片');
    if( !$res = $trello->post('/1/cards', $param) )
      return MyLineBotMsg::create()->text('無法傳送trello卡片');

    Log::info( "=============================\r\n" .json_encode($res) );
    Log::info('hehehe1');

    //將卡片\存入資料庫
    $param = array(
      'list_id' => $list->id,
      'key_id' => $res['id'],
      'source_id' => $source->id,
      'name' => $res['name'],
      'code' => $res['shortLink'],
      'status' => Card::STATUS_READY,
    );
    Log::info('param:' . json_encode($param));
    Log::info('hehehe2');

    if( !$card = Card::create($param) )
      return MyLineBotMsg::create()->text('資料庫處理失敗');

    Log::info('hehehe3');

    //還原初始
    $source->process = '';
    $source->save();

    Log::info('hehehe4');

    return MyLineBotMsg::create()->text('已將信件送出給客服系統，請耐心等待回覆！');
  }
}
