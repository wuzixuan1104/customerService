<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Load::lib ('MyLineBot.php');

class LineTool {

  public function __construct() {
  }

  /* 目的：開始功能選單
   * 呼叫此function 的模式 Follow, join, 傳入字串'hello'
   */
  public static function start() {
    if( !$lists = TList::find('all') )
      return false;

    $arr[] = FlexText::create('問題類別')->setWeight('bold')->setSize('lg');

    foreach($lists as $list) {
      $arr[] = FlexSeparator::create();
      $arr[] = FlexBox::create([
                  FlexBox::create([
                    FlexBox::create([ FlexText::create($list->name) ])->setLayout('baseline')
                  ])->setLayout('vertical')->setFlex(7),
                  FlexSeparator::create(),
                  FlexButton::create('primary')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction( FlexAction::postback( '選擇', $list->name, json_encode(array('lib' => 'LineTool', 'method' => 'getList', 'param' => array('list_id' => $list->key_id) ) ) ) )
               ])->setLayout('horizontal')->setSpacing('md');
    }
    $multis = [];
    $multis[] = MyLineBotMsg::create()->text('感謝您使用我們的客服信箱，請填選以下流程！');

    $multis[] = MyLineBotMsg::create()->flex('選擇問題類別', FlexBubble::create([
      'body' => FlexBox::create($arr)->setLayout('vertical')->setSpacing('md')]));
    
    return MyLineBotMsg::create()->multi ($multis);
  }

  //取得客服問題分類表
  public static function getList($param, $source) {
    if( empty($param['list_id']) )
      return false;

    $source->process = json_encode( array('idCard' => '', 'idList' => $param['list_id'], 'content' => '', 'date' => date('Y-m-d')) );
    $source->save();

    return MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
      MyLineBotMsg::create()->templateConfirm( '輸入問題之後請點擊', [
        MyLineBotActionMsg::create()->message('取消', '您已按了取消'),
        MyLineBotActionMsg::create()->postback('送出', array('lib' => 'TrelloTool', 'method' => 'sendCard', 'param' => array() ), '您已按了送出'),
      ]));
  }

  //儲存個人處理程序
  public static function saveSourceProcess($source, $text) {
    $process = json_decode($source->process, true);
    if( empty($process) || empty($text) )
      return false;

    if( $process['date'] && strtotime('today') > strtotime('+1 week', strtotime($process['date'])) ) {
      $process = '';
    } else {
      $process['content'] .= $text . "\r\n";
      $process = json_encode($process);
    }
    $source->process = $process;
    $source->save();
  }

  //評分表
  public static function sendScoreForm($cardId, $servicerId) {
    if(!$cardId || !$servicerId)
      return false;

    $buttons = [];
    foreach(array_chunk(range(1, 10), 3) as $gValue) {
      $tmp = [];
      foreach($gValue as $v) 
        $tmp[] = FlexButton::create('primary')->setAction(FlexAction::postBack((string)$v, $v . '分', json_encode(array('lib' => 'LineTool', 'method' => 'getScore', 'param' => array('card_id' => $cardId, 'servicer_id' => $servicerId, 'score' => $v))  ) ) );
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

class RichmenuEvent {

  public static function qa() {

    if(!$source = func_get_args()[1]) 
      return false;

    if(!$cards = Card::find('all', array('where' => array('source_id = ? AND created_at >= date_sub(now(), interval 1 month)', $source->id))))
      return MyLineBotMsg::create()->text('尚無進行中的問題');

    array_map(function($card) use (&$format) {
      !$card->list ? null : $format[$card->list->name][] = $card;
    }, $cards);
    
    $flexes = $bubbles = [];
    $cnt = 0;
    foreach($format as $formatTypes => $contents) {
      if(count($bubbles) > 4)
          break;

      $flexes[] = FlexText::create($formatTypes)->setColor('#12776e')->setWeight('bold');
      $flexes[] = FlexSeparator::create();

      $cnt++;

      foreach($contents as $content) {
        if(count($bubbles) > 4)
          break;

        $flexes[] = FlexBox::create([
                      FlexBox::create([FlexBox::create([
                        FlexText::create('Q.' .  $content->name),
                        FlexBox::create([FlexText::create(Card::STATUS_DEAL == $content->status ? '處理中...' : '待處理')->setSize('xxs')->setAlign('start')->setColor(Card::STATUS_DEAL == $content->status ? '#f37370' : '#bbbbbb'), FlexText::create($content->created_at->format('Y-m-d'))->setSize('xxs')->setAlign('end')->setColor('#bbbbbb')])->setLayout('horizontal')->setMargin('lg')
                      ])->setLayout('vertical')])->setLayout('vertical')->setFlex(7),
                      FlexSeparator::create(),
                      FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換', '您已按了切換', json_encode(array('lib' => 'LineTool', 'method' => 'getScore', 'param' => array('card_id' => $content->id) )  ) ))
                    ])->setLayout('horizontal')->setSpacing('md');
        $flexes[] = FlexSeparator::create();

        if((++$cnt) >= 5) {
          $bubbles[] = FlexBubble::create([
                          'header' => FlexBox::create([ FlexText::create('問題列表 - 正在進行中')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
                          'body' => FlexBox::create($flexes)->setLayout('vertical')->setSpacing('md')->setMargin('sm'),
                          'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
                        ]);
          $flexes = [];
          $cnt = 0;
        }
      }
    }

    if($flexes) {
      $bubbles[] = FlexBubble::create([
                      'header' => FlexBox::create([ FlexText::create('問題列表 - 正在進行中')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
                      'body' => FlexBox::create($flexes)->setLayout('vertical')->setSpacing('md')->setMargin('sm'),
                      'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))]);
    }

    return MyLineBotMsg::create()->flex('問題列表 - 正在進行中', FlexCarousel::create($bubbles)); 

  }

  public static function menu() {
    
  }

  public static function contact() {

  }
}
