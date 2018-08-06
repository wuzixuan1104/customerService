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
    Load::lib('LineTool.php');

    $events = MyLineBot::events();

    foreach( $events as $event ) {

      if( !$source = Source::checkSourceExist($event) )
        continue;

      $speaker = Source::checkSpeakerExist($event);

      if (!$log = MyLineBotLog::init($source, $speaker, $event)->create())
        return false;

      switch( get_class($log) ) {
        case 'Join':
          if ( $msg = LineTool::start() )
            $msg->reply($event->getReplyToken());

          break;
        case 'Leave':
          break;
        case 'Follow':
          if ( $msg = LineTool::start() )
            $msg->reply($event->getReplyToken());

          break;
        case 'Unfollow':
          break;
        case 'Text':
          $pattern = 'hello';
          $pattern = !preg_match ('/\(\?P<k>.+\)/', $pattern) ? '/(?P<k>(' . $pattern . '))/i' : ('/(' . $pattern . ')/i');
          preg_match_all ($pattern, $log->text, $result);

          //傳送hello時跳出開始menu
          if ($result['k'] && $msg = LineTool::start() )
            $msg->reply($event->getReplyToken());
          else {
            $buttons = [];
            foreach(array_chunk(range(1, 10), 3) as $gValue) {
              $tmp = [];
              foreach($gValue as $v) 
                $tmp[] = FlexButton::create('primary')->setAction(FlexAction::postBack($v, '123', '123') );
              $buttons[] = FlexBox::create($tmp)->setLayout('horizontal')->setSpacing('sm');
            }
            array_push($buttons, FlexSeparator::create()->setMargin('lg'));
            
            MyLineBotMsg::create()->flex('test', FlexBubble::create([
              'header'  => FlexBox::create([
                            FlexText::create('客服評分表')->setSize('lg')->setWeight('bold'), 
                            FlexText::create('(請點選1~10分)')])->setLayout('horizontal'),
              'body'    => FlexBox::create([
                            FlexBox::create($buttons)->setLayout('vertical')->setMargin('lg')->setSpacing('sm')
                           ])->setLayout('vertical'),
              'footer'  => FlexBox::create([
                            FlexButton::create('secondary')->setAction(FlexAction::postBack('意見回饋', '123', '123'))->setMargin('sm')])->setLayout('vertical')
            ]))->reply($event->getReplyToken());
            
            Log::info('test123');
          }

          //檢查Source process是否非空，是則新增進去
          if( !empty($source->process) )
            LineTool::saveSourceProcess($source, $event->getText());

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
               && method_exists($lib = $data['lib'], $method = $data['method']) && $msg = $lib::$method( $data['param'], $source ) ) )
            return false;

          $msg->reply($event->getReplyToken());
          break;
      }
    }
  }

}
