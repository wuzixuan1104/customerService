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
    Log::info('type: '.$data['action']['type']);
    Log::info('web:'. Webhook::TYPE_UPDATE_CHECK_ITEM_STATE_ON_CARD);
    switch($data['action']['type']) {
      case Webhook::TYPE_COMMENT_CARD:
        if( !$card = Card::find_by_key_id($data['model']['id']) )
          return false;

        $card->source->process = json_encode( array('idCard' => $card->key_id, 'idList' => $card->list->key_id, 'content' => '', 'date' => date('Y-m-d')) );
        $card->source->save();

        $sid = $card->source->sid;
        break;

      case Webhook::TYPE_UPDATE_CHECK_ITEM_STATE_ON_CARD:
        Log::info( 'card id:' .$data['action']['data']['card']['id']);
        if( !$card = Card::find_by_key_id($data['action']['data']['card']['id']) )
          return false;

        $item = $data['action']['data']['checkItem'];
        Log::info('itme:' . json_encode($item));
        Log::info('card tpye:' . json_encode(Card::$statusTexts) );
        $statusTexts = array_flip(Card::$statusTexts);

        Log::info('typeText: ' . json_encode($typeTexts));

        if( $statusTexts[$item['name']] == Card::TYPE_PROCESS && $card->status != Card::TYPE_FINISH)
          $card->status = ($item['state'] == 'complete') ? Card::TYPE_PROCESS : Card::TYPE_READY;
        else
          $card->status = ($item['state'] == 'complete') ? Card::TYPE_FINISH : Card::TYPE_PROCESS;
        Log::info('status: ' . $card->status);
        $card->save();

        break;
    }

    if(!$sid)
      return false;

    Load::lib('MyLineBot.php');

    $bot = MyLineBot::create();
    $msg = MyLineBotMsg::create ()
            ->multi ([
              MyLineBotMsg::create ()->text ($data['action']['data']['text']),
              MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
                MyLineBotMsg::create()->templateConfirm( '輸入問題之後請點擊', [
                  MyLineBotActionMsg::create()->message('取消', '您已按了取消'),
                  MyLineBotActionMsg::create()->postback('送出', array('lib' => 'TrelloTool', 'method' => 'replyCard', 'param' => array() ), '您已按了送出'),
                ]))
            ]);

    $response = $bot->pushMessage($sid, $msg->builder);

    $webhook->response = $response->getHTTPStatus() . ' ' . $response->getRawBody();
    $webhook->save();

    return true;
  }
}
