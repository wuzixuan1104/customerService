<?php defined ('OACI') || exit ('此檔案不允許讀取。');
Load::lib('MyLineBot.php');

class Callback {
  public $webhook = null;
  public $servicer = null;
  public $action = null;
  public $model = null;

  public function __construct($data) {
    if(!$data)
      return false;

    isset($data['action']) && $this->action = $data['action'];
    isset($data['model']) && $this->model = $data['model'];

    if(!$this->servicer = Servicer::find_by_key_id($this->action['idMemberCreator']))
      return false;

    if(!$this->webhook = Webhook::create(['key_id' => $this->action['id'], 'type' => $this->action['type'], 'mode_id' => $this->model['id'], 'servicer_id' => $this->servicer->id, 'content' => json_encode($this->action['data'])]))
      return false;
  }

  public static function create(array $data) {
    return new Callback($data);
  }

  public function commentCard() {
    if(!$card = Card::find_by_key_id($this->model['id']))
      return false;

    $card->source->process = json_encode(['idCard' => $card->key_id, 'idList' => $card->list->key_id, 'content' => '', 'date' => date('Y-m-d')] );
    $card->source->save();

    if(!$history = History::create(['card_id' => $card->id, 'servicer_id' => $this->servicer->id, 'content' => $this->action['data']['text']]))
      return false;

    if(!$sid = $card->source->sid)
      return false;

    $bot = MyLineBot::create();
    $msg = MyLineBotMsg::create ()->multi ([
              MyLineBotMsg::create ()->text ($this->action['data']['text']),
              MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
                MyLineBotMsg::create()->templateConfirm( '輸入之後請點擊', [
                  MyLineBotActionMsg::create()->message('取消', '您已按了取消'),
                  MyLineBotActionMsg::create()->postback('送出', ['lib' => 'trello/Send', 'method' => 'reply', 'param' => [] ], '您已按了送出'),]))
                  // MyLineBotActionMsg::create()->postback('送出', ['lib' => 'TrelloTool', 'method' => 'replyCard', 'param' => [] ], '您已按了送出'),]))
            ]);

    $response = $bot->pushMessage($sid, $msg->builder);
    $this->webhook->response = $response->getHTTPStatus() . ' ' . $response->getRawBody();
    $this->webhook->save();
  }

  public function updateCheckItemStateOnCard() {
    if( !$card = Card::find_by_key_id($this->action['data']['card']['id']) )
      return false;

    $item = $this->action['data']['checkItem'];
    $statusTexts = array_flip(Card::$statusTexts);

    $oriStatus = $card->status;
  
    //是否變更 trello傳來的狀態 與 Card Status
    if( $statusTexts[$item['name']] == Card::STATUS_DEAL && $card->status != Card::STATUS_FINISH)
      $card->status = ($item['state'] == 'complete') ? Card::STATUS_DEAL : Card::STATUS_YET;
    elseif( $statusTexts[$item['name']] == Card::STATUS_FINISH )
      $card->status = ($item['state'] == 'complete') ? Card::STATUS_FINISH : Card::STATUS_DEAL;

    $card->save();

    if($card->status == Card::STATUS_FINISH) {
      Load::lib('LineTool.php');
      if(!$sid = $card->source->sid)
        return false;

      $bot = MyLineBot::create();
      if(!$msg = LineTool::sendScoreForm($card->id, $this->servicer->id))
        return false;

      $response = $bot->pushMessage($sid, $msg->builder);
      $this->webhook->response = $response->getHTTPStatus() . ' ' . $response->getRawBody();
      $this->webhook->save();
    }
    
    //label標籤 將舊的刪除 添加新的
    if( $oriStatus != $card->status && $labels = Label::find('all', ['select' => 'key_id, tag', 'where' => ['tag IN (?)', [$oriStatus, $card->status] ] ])) {
      Load::lib('trello/Send.php');
      Send::updateCardStatus($card, $labels, $oriStatus);
    }
  }

  public function deleteCard() {
    if( $card = Card::find_by_key_id($this->action['data']['card']['id']) )
      if( $card->destroy() )
        return false;
  }

}