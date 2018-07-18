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
      'content' => $data['action']['data']['text'],
    );

    if( !$webhook = Webhook::create($param) )
      return false;

    switch($data['action']['type']) {
      case Webhook::TYPE_COMMENT_CARD:
        if( !$card = Card::find_by_key_id($data['model']['id']) )
          return false;

        $sid = $card->source->sid;
        break;
    }

    if(!$sid)
      return false;

    Load::lib('MyLineBot.php');

    $bot = MyLineBot::events();
    $msg = MyLineBotMsg::create()->text($data['action']['data']['text']);
    Log::info('msg:');
    $response = $bot->pushMessage($sid, $msg);
    Log::info('response');
    $webhook->response = $response->getHTTPStatus() . ' ' . $response->getRawBody();
    $webhook->save();
    Log::info('web save');
    return true;
  }
}
