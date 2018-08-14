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
    $data = func_get_arg();
    if(!(($source = $data[1]) && ($source->card->key_id)))
      return false;
    
    $process = json_decode($source->process, true);
    $source->process = '';
    $source->save();

    $trello = TrelloApi::create();
    if( !$oriCard = $trello->get('/1/cards/' . $source->card->key_id) )
      return MyLineBotMsg::create()->text('查無原本問題');

    if( !$trello->put('/1/cards/' . $source->card->key_id, array( 'desc' => $oriCard['desc'] . "\r\n### Re: " . date('Y-m-d H:i:s') . "\r\n" . $process['content'] . "\r\n" . "---" )) )
      return MyLineBotMsg::create()->text('送出失敗');

    return MyLineBotMsg::create()->text('已將信件送出給客服系統，請耐心等待回覆！');
  }
}