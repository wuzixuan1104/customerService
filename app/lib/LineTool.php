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

  public function getList() {

  }
}
