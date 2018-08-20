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
    Log::info('1');
    isset($data['action']) && $this->action = $data['action'];
    isset($data['model']) && $this->model = $data['model'];
    Log::info('2');
    if(!$this->servicer = Servicer::find_by_key_id($this->action['idMemberCreator']))
      return false;
    Log::info('3');
    if(!$this->webhook = Webhook::create(['key_id' => $this->action['id'], 'type' => $this->action['type'], 'mode_id' => $this->model['id'], 'servicer_id' => $this->servicer->id, 'content' => json_encode($this->action['data'])]))
      return false;
    Log::info('4');
  }

  public static function create(array $data) {
    return new Callback($data);
  }

  public function commentCard() {
    if(!$card = Card::find_by_key_id($this->model['id']))
      return false;
    Log::info('card 1');
    $card->source->process = json_encode(['idCard' => $card->key_id, 'idList' => $card->list->key_id, 'content' => '', 'date' => date('Y-m-d')] );
    $card->source->save();
    Log::info('card 2');
    if(!$history = History::create(['card_id' => $card->id, 'servicer_id' => $this->servicer->id, 'content' => $this->action['data']['text']]))
      return false;
    Log::info('card 3');
    if(!$sid = $card->source->sid)
      return false;
    Log::info('card 4');
    $bot = MyLineBot::create();
    $msg = MyLineBotMsg::create ()->multi ([
              MyLineBotMsg::create ()->text ($this->action['data']['text']),
              MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
                MyLineBotMsg::create()->templateConfirm( '輸入之後請點擊', [
                  MyLineBotActionMsg::create()->message('取消', '您已按了取消'),
                  MyLineBotActionMsg::create()->postback('送出', ['lib' => 'TrelloTool', 'method' => 'replyCard', 'param' => [] ], '您已按了送出'),]))
            ]);
    Log::info('card 5');
    $response = $bot->pushMessage($sid, $msg->builder);
    $this->webhook->response = $response->getHTTPStatus() . ' ' . $response->getRawBody();
    $this->webhook->save();
    Log::info('card 6');
  }
}