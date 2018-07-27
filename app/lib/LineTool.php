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
                    FlexBox::create([ FlexText::create('檢舉配送品質') ])->setLayout('baseline')
                  ])->setLayout('vertical')->setFlex(7),
                  FlexSeparator::create(),
                  FlexButton::create('primary')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction( FlexAction::postback( '選擇', '123', json_encode(array('lib' => 'LineTool', 'method' => 'getList', 'param' => array('list_id' => '123') ) ) ) )
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
}
