<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Load::lib ('MyLineBot.php');

class CardResponse {
  public static function checkout() {
    $args = func_get_args();
    $params = $args[0];
    $source = $args[1];

    if(!(isset($params['title']) && isset($params['content']) && isset($params['card_id']) && isset($params['datetime']) ))
      return false;

    if(!$card = Card::find_by_id($params['card_id']))
      return false;

    ($source->process = json_encode( array('idCard' => $card->key_id, 'idList' => $card->list->key_id, 'content' => '', 'date' => date('Y-m-d')) )) && ($source->card_id = $card->id) && $source->save();
   
    $flexes = [];
    $flexes[] = FlexText::create('最近更新時間：'. $params['datetime'])->setColor('#aaaaaa')->setSize('xxs')->setAlign('start');

    if($contents = explode("\r\n", $params['content']))
      foreach($contents as $content) 
        $content && $flexes[] = FlexText::create($content)->setSize('sm');
    
    FlexButton::create('primary')->setColor('#f97172')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('回覆訊息後按此送出', '您按了送出訊息', json_encode(['lib' => 'trello/Send', 'class' => 'Send', 'method' => 'reply', 'param' => []])));
    
    return MyLineBotMsg::create()->flex('檢視客服人員回應內容', FlexBubble::create([
          'header' => FlexBox::create([FlexText::create($params['title'])->setWeight('bold')->setSize('md')->setColor('#e8f6f2')])->setSpacing('xs')->setLayout('horizontal'),
          'body' => FlexBox::create($flexes)->setLayout('vertical')->setSpacing('md'),
          'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
        ]));
  }
}