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
    Log::info('======================================');
    Log::info(file_get_contents('php://input'));
    die;
    $data = json_decode(file_get_contents('php://input'), true);

    if( !isset($data['action']['type']) || !isset(Webhook::$typeTexts[$data['action']['type']]) )
      return false;

    if( !$servicer = Servicer::find_by_key_id($data['action']['idMemberCreator']) )
      return false;

    $param = array(
      'key_id' => $data['action']['id'],
      'type' => $data['action']['type'],
      'model_id' => $data['model']['id'],
      'servicer_id' => $servicer->id,
      'content' => json_encode($data['action']['data']),
    );

    if( !$webhook = Webhook::create($param) )
      return false;

    Load::lib('MyLineBot.php');

    switch($data['action']['type']) {
      case Webhook::TYPE_COMMENT_CARD:
        if( !$card = Card::find_by_key_id($data['model']['id']) )
          return false;

        $card->source->process = json_encode( array('idCard' => $card->key_id, 'idList' => $card->list->key_id, 'content' => '', 'date' => date('Y-m-d')) );
        $card->source->save();

        if(!$sid = $card->source->sid)
          return false;

        $bot = MyLineBot::create();
        $msg = MyLineBotMsg::create ()->multi ([
                  MyLineBotMsg::create ()->text ($data['action']['data']['text']),
                  MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
                    MyLineBotMsg::create()->templateConfirm( '輸入之後請點擊', [
                      MyLineBotActionMsg::create()->message('取消', '您已按了取消'),
                      MyLineBotActionMsg::create()->postback('送出', array('lib' => 'TrelloTool', 'method' => 'replyCard', 'param' => array() ), '您已按了送出'),]))
                ]);

        $response = $bot->pushMessage($sid, $msg->builder);
        $webhook->response = $response->getHTTPStatus() . ' ' . $response->getRawBody();
        $webhook->save();
        break;

      case Webhook::TYPE_UPDATE_CHECK_ITEM_STATE_ON_CARD:
        if( !$card = Card::find_by_key_id($data['action']['data']['card']['id']) )
          return false;

        $item = $data['action']['data']['checkItem'];
        $statusTexts = array_flip(Card::$statusTexts);

        $oriStatus = $card->status;
        //是否變更 trello傳來的狀態 與 Card Status
        if( $statusTexts[$item['name']] == Card::STATUS_DEAL && $card->status != Card::STATUS_FINISH)
          $card->status = ($item['state'] == 'complete') ? Card::STATUS_DEAL : Card::STATUS_YET;
        elseif( $statusTexts[$item['name']] == Card::STATUS_FINISH )
          $card->status = ($item['state'] == 'complete') ? Card::STATUS_FINISH : Card::STATUS_DEAL;

        $card->save();

        //label標籤 將舊的刪除 添加新的
        if( $oriStatus != $card->status && $labels = Label::find('all', array( 'select' => 'key_id, tag', 'where' => array('tag IN (?)', array($oriStatus, $card->status) ) ) ) ) {
          Load::lib('TrelloApi.php');
          $trello = TrelloApi::create();

          foreach( $labels as $label ) {
            switch( $label->tag ) {
              case $oriStatus:
                if( !$trello->delete('/1/cards/' . $card->key_id . '/idLabels/' . $label->key_id ) )
                  return false;
                break;
              case $card->status:
                if( !$trello->post('/1/cards/' . $card->key_id . '/idLabels', array('value' => $label->key_id) ) )
                  return false;
                break;
            }
          }
        }

        break;

      case Webhook::TYPE_DELETE_CARD:
        if( $card = Card::find_by_key_id($data['action']['data']['card']['id']) )
          if( $card->destroy() )
            return false;
        break;
    }

    return true;
  }
}
