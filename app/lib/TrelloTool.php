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
    if( !$res = $trello->post('/1/cards', $param) )
      return MyLineBotMsg::create()->text('無法傳送trello卡片');

    //新增checklist
    if( !$ckList = $trello->post('/1/checklists', array('idCard' => $res['id'], 'name' => '問題進度', 'pos' => 'top') ) )
      return MyLineBotMsg::create()->text('無法新增trello問題進度列表');

    //新增checkItem
    if( !$trello->post('/1/checklists/' . $ckList['id'] . '/checkItems', array('name' => '處理中', 'pos' => 'bottom') ) ||
        !$trello->post('/1/checklists/' . $ckList['id'] . '/checkItems', array('name' => '已完成', 'pos' => 'bottom') ) )
      return MyLineBotMsg::create()->text('無法新增trello問題列表項目');

    //新增webhook
    if( !$hook = $trello->setWebhook($res['id'], $res['name']) )
      return MyLineBotMsg::create()->text('卡片建立webhook失敗！');

    //將卡片\存入資料庫
    $param = array(
      'list_id' => $list->id,
      'key_id' => $res['id'],
      'source_id' => $source->id,
      'webhook_key_id' => $hook['id'],
      'name' => $res['name'],
      'code' => $res['shortLink'],
      'status' => Card::STATUS_READY,
    );

    if( !$card = Card::create($param) )
      return MyLineBotMsg::create()->text('資料庫處理失敗');

    //將source的idCard做更新
    $source->process = '';
    $source->save();

    return MyLineBotMsg::create()->text('已將信件送出給客服系統，請耐心等待回覆！');
  }

  public static function replyCard() {
    $source = func_get_args()[1];

    $process = json_decode($source->process, true);
    $source->process = '';
    $source->save();

    if( !isset($process['idCard']) || empty($process['idCard']) )
      return MyLineBotMsg::create()->text('查無此問題，無法操作此步驟');

    if( $process['date'] && strtotime('today') > strtotime('+1 week', strtotime($process['date'])) ) {
      return MyLineBotMsg::create()->text('此問題已超過7天未送出，無法操作此步驟');
    }

    $trello = TrelloApi::create();
    //取得原本內容
    if( !$oriCard = $trello->get('/1/cards/' . $process['idCard']) )
      return MyLineBotMsg::create()->text('查無原本問題');

    if( !$trello->put('/1/cards/' . $process['idCard'], array( 'desc' => $oriCard['desc'] . "\r\n### Re: " . date('Y-m-d H:i:s') . "\r\n" . $process['content'] . "\r\n" . "---" )) )
      return MyLineBotMsg::create()->text('送出失敗');

    return MyLineBotMsg::create()->text('已將信件送出給客服系統，請耐心等待回覆！');
  }
}
