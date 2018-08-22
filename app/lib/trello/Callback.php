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

    if(Source::isCurrentCard($sid, $card->id)) {

      $flexes = [];
      $flexes[] = FlexText::create('傳送時間：'. date('Y-m-d H:i:s'))->setColor('#aaaaaa')->setSize('xxs')->setAlign('start');
      $contents = explode("\r\n", $this->action['data']['text']);
      foreach($contents as $content) 
        $content && $flexes[] = FlexText::create($content)->setSize('xs');
      $flexes[] = FlexButton::create('primary')->setColor('#f97172')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('回覆訊息後按此送出', '您按了送出訊息', json_encode(['lib' => 'trello/Send', 'class' => 'Send', 'method' => 'reply', 'param' => []])));
     
      $msg = MyLineBotMsg::create()->flex('檢視問題內容', FlexBubble::create([
              'header' => FlexBox::create([FlexText::create($title)->setWeight('bold')->setSize('md')->setColor('#e8f6f2')])->setSpacing('xs')->setLayout('horizontal'),
              'body' => FlexBox::create($flexes)->setLayout('vertical')->setSpacing('md'),
              'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
            ]));
     
      // $msg = MyLineBotMsg::create ()->multi ([
      //           MyLineBotMsg::create ()->text ($this->action['data']['text']),
      //           MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
      //             MyLineBotMsg::create()->templateConfirm( '輸入之後請點擊', [
      //               MyLineBotActionMsg::create()->message('取消', '您已按了取消'),
      //               MyLineBotActionMsg::create()->postback('送出', ['lib' => 'trello/Send', 'class' => 'Send','method' => 'reply', 'param' => [] ], '您已按了送出'),]))
      //         ]);
    } else {
      $msg = MyLineBotMsg::create()->flex('您有個問題客服已回應，可以前往查看', FlexBubble::create([
          'header' => FlexBox::create([FlexText::create('您有個問題客服已回應，可以前往查看')->setWeight('bold')->setSize('sm')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
          'body' => FlexBox::create([FlexBox::create([FlexButton::create('primary')->setColor('#fbd785')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換問題並查看', null, json_encode(['lib' => 'postback/Other', 'class' => 'CardResponse', 'method' => 'checkout', 'param' => ['content' => $this->action['data']['text'], 'title' => $card->name, 'card_id' => $card->id, 'datetime' => date('Y-m-d H:i:s')]])))])->setLayout('vertical')->setMargin('xxl')->setSpacing('sm')])->setLayout('vertical'),
          'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
        ]));
    }


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
      Load::lib('other/Score.php');
      if(!$sid = $card->source->sid)
        return false;

      $bot = MyLineBot::create();
      if(!$msg = Score::sendForm($card->id, $this->servicer->id))
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