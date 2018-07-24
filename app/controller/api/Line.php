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

        if ( $msg = LineTool::start() )
          $msg->reply($event->getReplyToken());
        die;
          $qa = [''];

          $builder = MyLineBotMsg::create()->flex('test', MyLineBotMsg::create()->flexBubbleBuilder()
                      ->setBody( FlexComponent::create()->setType('box')->setLayout('vertical')->setSpacing('md')->setContents([
                        FlexComponent::create()->setType('text')->setText('客服評分表')->setWeight("bold")->setSize('lg'),

                        FlexComponent::create()->setType('separator'),
                        FlexComponent::create()->setType('box')->setLayout('horizontal')->setSpacing('md')->setContents([
                          FlexComponent::create()->setType('box')->setLayout('vertical')->setFlex(7)->setContents([
                            FlexComponent::create()->setType('box')->setLayout('baseline')->setContents([
                              FlexComponent::create()->setType('text')->setText('Q1'),
                            ])
                          ]),
                          FlexComponent::create()->setType('separator'),
                          FlexComponent::create()->setType('button')->setFlex(3)->setHeight('sm')->setGravity('center')->setStyle('secondary')->setAction( FlexAction::postback('click', 'data=123', 'A1') )
                        ]),

                        FlexComponent::create()->setType('separator'),
                        FlexComponent::create()->setType('box')->setLayout('horizontal')->setSpacing('md')->setContents([
                          FlexComponent::create()->setType('box')->setLayout('vertical')->setFlex(7)->setContents([
                            FlexComponent::create()->setType('box')->setLayout('baseline')->setContents([
                              FlexComponent::create()->setType('text')->setText('Q2'),
                            ])
                          ]),
                          FlexComponent::create()->setType('separator'),
                          FlexComponent::create()->setType('button')->setFlex(3)->setHeight('sm')->setGravity('center')->setStyle('secondary')->setAction( FlexAction::postback('click', 'data=123', 'A1') )
                        ]),
                      ]))

                      ->setFooter( FlexComponent::create()->setType('box')->setLayout('vertical')->setContents([
                        FlexComponent::create()->setType('button')->setAction( FlexAction::postback('Table', 'data=123', 'A1') )->setStyle('primary')
                      ]) ))->reply($event->getReplyToken());


          print_r($builder);
          die;

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
