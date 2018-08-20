<?php defined ('OACI') || exit ('此檔案不允許讀取。');
Load::lib('MyLineBot.php');

class Callback {
  public $webhook = null;
  public $content = [];
  public $servicer = null;
  public $action = null;
  public $model = null;

  public function __construct($data) {
    if(!$data)
      return false;

    isset($data['action']) && $this->action = $data['action'];
    isset($data['model']) && $this->model = $data['model'];

    if( !$this->servicer = Servicer::find_by_key_id($this->action['idMemberCreator']) )
      return false;

    if( !$webhook = Webhook::create(['key_id' => $this->action['id'], 'type' => $this->action['type'], 'mode_id' => $this->model['id'], 'servicer_id' => $this->servicer->id, 'content' => json_encode($this->action['data'])]) )
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
                  MyLineBotActionMsg::create()->postback('送出', ['lib' => 'TrelloTool', 'method' => 'replyCard', 'param' => [] ], '您已按了送出'),]))
            ]);

    $response = $bot->pushMessage($sid, $msg->builder);
    $webhook->response = $response->getHTTPStatus() . ' ' . $response->getRawBody();
    $webhook->save();
  }
}