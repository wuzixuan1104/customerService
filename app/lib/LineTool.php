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
    Log::info('start');

    if( !$lists = TList::find('all') )
      return false;

    $arr = [];

    foreach( $lists as $vlist ) {
      $arr[] = FlexComponent::create()->setType('separator');
      $arr[] = FlexComponent::create()->setType('box')->setLayout('horizontal')->setSpacing('md')->setContents([
        FlexComponent::create()->setType('box')->setLayout('vertical')->setFlex(7)->setContents([
          FlexComponent::create()->setType('box')->setLayout('baseline')->setContents([
            FlexComponent::create()->setType('text')->setText($vlist->name),
          ])
        ]),
        FlexComponent::create()->setType('separator'),
        FlexComponent::create()->setType('button')->setFlex(3)->setHeight('sm')->setGravity('center')->setStyle('primary')->setAction( FlexAction::postback( '選擇', array('lib' => 'LineTool', 'method' => 'getList', 'param' => array('list_id' => $vlist->key_id) ), $vlist->name ) )
      ]);

    }

      // $a = MyLineBotMsg::create()->flex('選擇問題類別', MyLineBotMsg::create()->flexBubbleBuilder()
      //   ->setBody( FlexComponent::create()->setType('box')->setLayout('vertical')->setSpacing('md')->setContents( array_merge([
      //     FlexComponent::create()->setType('text')->setText('問題類別')->setWeight("bold")->setSize('lg'),
      //   ], $arr))));

    // foreach( array_chunk( $lists, 3 ) as $key => $list ) {
    //   $actionArr = [];
    //   foreach( $list as $vlist )
    //     $actionArr[] = MyLineBotActionMsg::create()->postback( $vlist->name, array('lib' => 'LineTool', 'method' => 'getList', 'param' => array('list_id' => $vlist->key_id) ), $vlist->name);
    //
    //   //檢查是否每項為3個
    //   if( ($listSub = 3 - count($list)) != 0 )
    //     for( $i = 0; $i < $listSub; $i++ )
    //       $actionArr[] = MyLineBotActionMsg::create()->postback( '-', array(), '-');
    //
    //   $columnArr[] = MyLineBotMsg::create()->templateCarouselColumn('請選擇問題類別', '-', null, $actionArr);
    // }
    //
    $multiArr = [ MyLineBotMsg::create ()->text ('感謝您使用客服信箱，請填寫以下程序，待客服人員回覆:)') ];
    $multiArr = array_merge( $multiArr, [ MyLineBotMsg::create()->flex('選擇問題類別', MyLineBotMsg::create()->flexBubbleBuilder()
      ->setBody( FlexComponent::create()->setType('box')->setLayout('vertical')->setSpacing('md')->setContents( array_merge([
        FlexComponent::create()->setType('text')->setText('問題類別')->setWeight("bold")->setSize('lg'),
      ], $arr)))) ]);
    // print_r($multiArr);
    // die;
    return MyLineBotMsg::create()->multi ($multiArr);
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
