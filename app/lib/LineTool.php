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

    foreach( array_chunk( $lists, 3 ) as $key => $list ) {
      $actionArr = [];
      foreach( $list as $vlist )
        $actionArr[] = MyLineBotActionMsg::create()->postback( $vlist->name, array('lib' => 'LineTool', 'method' => 'getList', 'param' => array('list_id' => $vlist->key_id) ), $vlist->name);

      //檢查是否每項為3個
      if( ($listSub = 3 - count($list)) != 0 )
        for( $i = 0; $i < $listSub; $i++ )
          $actionArr[] = MyLineBotActionMsg::create()->postback( '-', array(), '-');

      $columnArr[] = MyLineBotMsg::create()->templateCarouselColumn('請選擇問題類別', '-', null, $actionArr);
    }

    $multiArr = [ MyLineBotMsg::create ()->text ('感謝您使用客服信箱，請填寫以下程序，待客服人員回覆:)') ];
    $multiArr = array_merge( $multiArr, array_map( function($column) {
      return  MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
        MyLineBotMsg::create()->templateCarousel( $column )
      );
    }, array_chunk($columnArr, 10) ));
    Log::info('lineTool');
    return MyLineBotMsg::create()->multi ($multiArr);
  }

  public static function getList($param, $log) {
    if( empty($param['list_id']) )
      return false;

    if( !$source = Source::find_by_id($log->speaker_id) )
      return false;

    $source->process = json_encode( array('card_id' => '', 'list_id' => $param['list_id'], 'content' => '', 'date' => date('Y-m-d')) );
    $source->save();

    return MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
      MyLineBotMsg::create()->templateConfirm( '輸入問題之後請點擊', [
        MyLineBotActionMsg::create()->message('取消', '此按鈕目前無效'),
        MyLineBotActionMsg::create()->postback('送出', array('lib' => 'LineTool', 'method' => 'sendCard', 'param' => array() ), '已送出，請耐心等待客服人員回覆，感謝您！'),
      ]));
  }

  public static function sendCard() {

  }
}
