<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Line extends ApiController {
  static $cache;
  public function __construct() {
    parent::__construct();

  }

  public function index() {
    Load::lib('MyLineBot.php');

    $events = MyLineBot::events();
    foreach( $events as $event ) {
    
      if( !$source = Source::checkSourceExist($event) )
        continue;

      $speaker = Source::checkSpeakerExist($event);

      if (!$log = MyLineBotLog::init($source, $speaker, $event)->create())
        return false;

      switch( get_class($log) ) {
        case 'Join':
          // if ( $msg = LineTool::start() )
          //   $msg->reply($event->getReplyToken());
          break;
        case 'Follow':
          // if ( $msg = LineTool::start() )
          //   $msg->reply($event->getReplyToken());
          break;
        case 'Text':
          //檢查Source process是否非空，是則新增進去
          if(!empty($source->process))
            $source->saveProcess($event->getText());
          break;
        case 'Postback':
          $data = json_decode( $log->data, true );
          if( !( isset( $data['lib'], $data['class'], $data['method'] ) && ( isset( self::$cache['lib'][$data['lib']] ) ? true : ( Load::lib($data['lib'] . '.php') ? self::$cache['lib'][$data['lib']] = true : true ) )
            && method_exists($class = $data['class'], $method = $data['method']) && $msg = $class::$method( $data['param'], $source ) ) )
            return false;
       
          $msg->reply($event->getReplyToken());
          break;
      }
    }
  }

}
