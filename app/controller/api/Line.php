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
    // Load::lib('ForexProcess.php');
    // Load::sysFunc('file.php');

    $events = MyLineBot::events();
    Log::info(234);
    Log::info($event);
    Log::info(123);
    foreach( $events as $event ) {

      if( !$source = Source::checkSourceExist($event) ) {
        Log::info('source fail');
        continue;
      }

      $speaker = Source::checkSpeakerExist($event);

      if (!$log = MyLineBotLog::init($source, $speaker, $event)->create()) {
        Log::info('log fail');
        return false;

      }

      switch( get_class($log) ) {
        case 'Join':
          // if ( $msg = ForexProcess::begin() )
          //   $msg->reply($event->getReplyToken());

          break;
        case 'Leave':
          break;
        case 'Follow':
          // if ( $msg = ForexProcess::begin() )
          //   $msg->reply($event->getReplyToken());

          break;
        case 'Unfollow':
          break;
        case 'Text':
          $pattern = 'hello';
          $pattern = !preg_match ('/\(\?P<k>.+\)/', $pattern) ? '/(?P<k>(' . $pattern . '))/i' : ('/(' . $pattern . ')/i');
          preg_match_all ($pattern, $log->text, $result);

          Log::info('text');
          MyLineBotMsg::create()
            ->text($event->getText())
            ->reply($event->getReplyToken());
          Log::info('end');



          // if ($result['k'] && $msg = ForexProcess::begin() )
          //   $msg->reply($event->getReplyToken());
          //
          // if( $msg = ForexProcess::getCalcResult($source, $event->getText()) )
          //   $msg->reply($event->getReplyToken());

          break;
        case 'Image':
          $url = $log->file->url();
          MyLineBotMsg::create()
            ->image($url, $url)
            ->reply($event->getReplyToken());
          break;

        case 'Video':
          $url = $log->file->url();
          MyLineBotMsg::create()
            ->video($url, $url)
            ->reply($event->getReplyToken());
          break;

        case 'Audio':
          $url = $log->file->url();
          MyLineBotMsg::create()
            ->audio($url, 60000)
            ->reply($event->getReplyToken());
          break;

        case 'Location':
          MyLineBotMsg::create()
            ->location($log->title, $log->address, $log->latitude, $log->longitude)
            ->reply($event->getReplyToken());
          break;

        case 'Postback':
          $data = json_decode( $log->data, true );
          if ( !( isset( $data['lib'], $data['method'] ) && ( isset( self::$cache['lib'][$data['lib']] ) ? true : ( Load::lib($data['lib'] . '.php') ? self::$cache['lib'][$data['lib']] = true : true ) )
               && method_exists($lib = $data['lib'], $method = $data['method']) && $msg = $lib::$method( $data['param'], $log ) ) )
            return false;

          $msg->reply($event->getReplyToken());
          break;
      }
    }
  }

}
