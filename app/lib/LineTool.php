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

    $flexes = [];
    foreach($lists as $list) {
      $flexes[] = FlexBox::create([
                    FlexBox::create([FlexText::create($list->name)])->setLayout('vertical')->setFlex(7),
                    FlexSeparator::create(),
                    FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('選擇', null, json_encode(['lib' => 'LineTool', 'class' => 'LineTool', 'method' => 'getList', 'param' => ['list_id' => $list->key_id]])))
                  ])->setLayout('horizontal')->setSpacing('md');
      $flexes[] = FlexSeparator::create();

    }

    return MyLineBotMsg::create()->flex('問題類別', FlexBubble::create([
            'header' => FlexBox::create([FlexText::create('選擇問題類別')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2')])->setSpacing('xs')->setLayout('horizontal'),
            'body' => FlexBox::create($flexes)->setLayout('vertical')->setSpacing('md')->setMargin('sm'),
            'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
          ]));
  }

  //取得客服問題分類表
  public static function getList($param, $source) {
    if( empty($param['list_id']) )
      return false;

    $source->process = json_encode( array('idCard' => '', 'idList' => $param['list_id'], 'content' => '', 'date' => date('Y-m-d')) );
    $source->save();

    return MyLineBotMsg::create()->flex('開始在下方輸入問題內容', FlexBubble::create([
            'header' => FlexBox::create([FlexText::create('開始在下方輸入問題內容')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
            'body' => FlexBox::create([FlexBox::create([FlexButton::create('primary')->setColor('#f97172')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('回覆訊息後按此送出', null, json_encode(['lib' => 'trello/Send', 'class' => 'Send', 'method' => 'card', 'param' => []])))])->setLayout('vertical')->setMargin('xxl')->setSpacing('sm')])->setLayout('vertical'),
            'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
          ]));

    // return MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
    //   MyLineBotMsg::create()->templateConfirm( '輸入問題之後請點擊', [
    //     MyLineBotActionMsg::create()->message('取消', '您已按了取消'),
    //     MyLineBotActionMsg::create()->postback('送出', array('lib' => 'TrelloTool', 'method' => 'sendCard', 'param' => array() ), '您已按了送出'),
    //   ]));
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

