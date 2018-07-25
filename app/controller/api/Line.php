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
    // Load::sysFunc('file.php');

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

        Load::lib('FlexFormat.php');

        $a = FlexBubble::create([
          'header' => FlexBox::create([ FlexText::create('Title')->setSize('xl')->setWeight('bold') ])->setLayout('vertical'),
          'hero' => FlexImage::create('https://sitthi.me:3807/static/fifa.jpg')->setSize('full')->setAspectRatio('20:13')->setAspectMode('cover'),
          'body' =>
            FlexBox::create([
              FlexBox::create([ FlexText::create('LIVE!!')->setSize('lg')->setColor('#555555')->setWeight('bold')->setAlign('center') ])->setLayout('vertical')->setSpacing('md'),
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
              FlexSeparator::create()->setMargin('lg'),
              FlexBox::create([
                FlexBox::create([
                  FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
                  FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
                ])->setLayout('horizontal')->setSpacing('sm'),
                FlexBox::create([
                  FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
                  FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
                ])->setLayout('horizontal')->setSpacing('sm'),
              ])->setLayout('vertical')->setMargin('lg')->setSpacing('sm'),
            ])->setLayout('vertical'),
          'footer' => FlexBox::create([ FlexButton::create('secondary')->setAction( FlexAction::postback('a', '123', 'e') )->setMargin('sm') ])->setLayout('vertical')

        ]);

        echo json_encode($a);
        die;
        FlexBox::create([ FlexText::create('Title')->setSize('xl')->setWeight('bold') ])->setLayout('vertical');




          $pattern = 'hello';
          $pattern = !preg_match ('/\(\?P<k>.+\)/', $pattern) ? '/(?P<k>(' . $pattern . '))/i' : ('/(' . $pattern . ')/i');
          preg_match_all ($pattern, $log->text, $result);
          //傳送hello時跳出開始menu
          if ($result['k'] && $msg = LineTool::start() )
            $msg->reply($event->getReplyToken());

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
