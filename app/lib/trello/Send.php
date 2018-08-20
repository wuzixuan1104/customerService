<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Load::lib('TrelloApi.php');

class Send {
  public static function reply() {
    $data = func_get_args();
    if(!(($source = $data[1]) && ($source->card->key_id)))
      return false;

    $process = json_decode($source->process, true);

    ($source->process = json_encode( array('idCard' => $source->card->key_id, 'idList' => $source->card->list->key_id, 'content' => '', 'date' => date('Y-m-d')) )) && $source->save();

    if(!History::create(['card_id' => $source->card_id, 'servicer_id' => 0, 'content' => $process['content']]) )
          return false;

    $trello = TrelloApi::create();
    if( !$oriCard = $trello->get('/1/cards/' . $source->card->key_id) )
      return MyLineBotMsg::create()->text('查無原本問題');

    if( !$trello->put('/1/cards/' . $source->card->key_id, array( 'desc' => $oriCard['desc'] . "\r\n### Re: " . date('Y-m-d H:i:s') . "\r\n" . $process['content'] . "\r\n" . "---" )) )
      return MyLineBotMsg::create()->text('送出失敗');

    return MyLineBotMsg::create()->flex('已將信件送出給客服系統，請耐心等待回覆！', FlexBubble::create([
          'header' => FlexBox::create([FlexText::create('已將信件送出給客服系統')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
          'body' => FlexBox::create([FlexBox::create([FlexButton::create('primary')->setColor('#fbd785')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('檢視送出的對話紀錄', null, json_encode(['lib' => 'postback/RichMenu', 'class' => 'Qa', 'method' => 'dialogRecord', 'param' => ['card_id' => $source->card_id, 'title' => date('Y-m-d H:i:s')]])))])->setLayout('vertical')->setMargin('xxl')->setSpacing('sm')])->setLayout('vertical'),
          'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
        ]));
  }

  public static function card() {
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
    if( !$label = Label::find('one', array('where' => array('tag = ?', Label::TAG_NEW) ) ) )
      return MyLineBotMsg::create()->text('系統發生問題');

    $param = array(
      'name' => 'From: ' . $source->title . ' ' . date('Y-m-d H:i:s'),
      'desc' => $process['content'] . "\r\n" . "---",
      'pos' => 'top',
      'due' => date( 'Y-m-d', strtotime('today + 1 week')),
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

    //新增label
    if( !$trello->post('/1/cards/' . $res['id'] . '/idLabels', array('value' => $label->key_id) ) )
      return MyLineBotMsg::create()->text('無法新增trello標籤');

    //新增webhook
    if( !$hook = $trello->setWebhook($res['id'], $res['name']) )
      return MyLineBotMsg::create()->text('卡片建立webhook失敗！');

    //將卡片\存入資料庫
    $param = array(
      'list_id' => $list->id,
      'key_id' => $res['id'],
      'label_ids' => json_encode( ['status' => $label->id, 'other' => ''] ),
      'source_id' => $source->id,
      'webhook_key_id' => $hook['id'],
      'name' => explode("\r\n", $process['content'])[0],
      'code' => $res['shortLink'],
      'status' => Card::STATUS_NEW,
    );

    if( !$card = Card::create($param) )
      return MyLineBotMsg::create()->text('資料庫處理失敗');

    if( !$history = History::create(['card_id' => $card->id, 'servicer_id' => 0, 'content' => $process['content']]))
      return MyLineBotMsg::create()->text('資料庫處理失敗');

    //將source的idCard做更新
    $source->process = '';
    $source->card_id = $card->id;
    $source->save();

    return MyLineBotMsg::create()->text('已將信件送出給客服系統，請耐心等待回覆！');
  }

  public static function updateCardStatus($card, $labels, $oriStatus) {
    if(!($card && $labels && $oriStatus))
      return false;

    $trello = TrelloApi::create();
    if( !$trello->put('/1/cards/' . $card->key_id, $card->status == Card::STATUS_FINISH ? array('dueComplete' => true, 'pos' => 'bottom') : array('dueComplete' => false, 'pos' => 'top') ) )
      return false;

    foreach( $labels as $label ) {
      switch( $label->tag ) {
        case $oriStatus:
          if( !$trello->delete('/1/cards/' . $card->key_id . '/idLabels/' . $label->key_id ) )
            return false;
          echo 1;
          break;
        case $card->status:
          if( !$trello->post('/1/cards/' . $card->key_id . '/idLabels', array('value' => $label->key_id) ) )
            return false;
          echo 2;
          break;
      }
    }
  }

}