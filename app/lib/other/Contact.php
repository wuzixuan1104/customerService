<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Load::lib ('MyLineBot.php');

class Contact {
  public static function send() {
    ($args = func_get_args()) && ($source = $args[1]) && ($process = json_decode($source->process, true));
    
    if(!$process['content'] || trim($process['content']) == "意見回饋\r\n")
      return MyLineBotMsg::create()->text('請於下方輸入文字訊息後再送出');

    if(!Opinion::create(['source_id' => $source->id, 'card_id' => 0, 'servicer_id' => 0, 'score' => '', 'content' => $process['content']]))
      return MyLineBotMsg::create()->text('系統發生錯誤，請重新嘗試');

    ($source->process = '') && $source->save();
    return MyLineBotMsg::create()->text('已順利送出，感謝您的意見回饋！');
  }
}

class Score {
  public static function sendForm($cardId, $servicerId) {
    if(!$cardId || !$servicerId)
      return false;

    $buttons = [];
    foreach(array_chunk(range(1, 10), 3) as $gValue) {
      $tmp = [];
      foreach($gValue as $v) 
        $tmp[] = FlexButton::create('primary')->setAction(FlexAction::postBack((string)$v, $v . '分', json_encode(array('lib' => 'other/Score', 'class' => 'Score', 'method' => 'getScore', 'param' => array('card_id' => $cardId, 'servicer_id' => $servicerId, 'score' => $v))  ) ) );
      $buttons[] = FlexBox::create($tmp)->setLayout('horizontal')->setSpacing('sm');
    }

    return MyLineBotMsg::create ()->multi ([
      MyLineBotMsg::create ()->text ('您的問題已處理完畢！煩請填寫客服評分表:)'),
      MyLineBotMsg::create()->flex('test', FlexBubble::create([
        'header'  => FlexBox::create([
                      FlexText::create('客服評分表')->setSize('lg')->setWeight('bold'), 
                      FlexText::create('(請點選1~10分)')])->setLayout('horizontal'),
        'body'    => FlexBox::create([
                      FlexBox::create($buttons)->setLayout('vertical')->setMargin('lg')->setSpacing('sm')
                     ])->setLayout('vertical')
      ]))
    ]);
  }

  public static function getScore($params, $source) {
    if( !$params['card_id'] || !$params['servicer_id'] || !$params['score'])
      return false;

    if(!$opinion = Opinion::find('one', array('where' => array('card_id = ?', $params['card_id']) )) ) {
      if(!$opinion = Opinion::create( array_merge($params, array('content' => '') ) ) )
        return false;
    } else {
      $opinion->score = $params['score'];
      $opinion->save(); 
    }
    return MyLineBotMsg::create()->text('感謝您的評分！');
  }
}