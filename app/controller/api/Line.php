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
          switch($log->text) {
            case 1:
              $msg = MyLineBotMsg::create()->flex('正在進行中的QA', FlexBubble::create([
                'header' => FlexBox::create([ FlexText::create('正在進行中的QA')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
                'body' => FlexBox::create([
                    FlexText::create('付款問題')->setColor('#12776e'),
                    FlexSeparator::create(),
                    FlexBox::create([
                      FlexBox::create([
                        FlexBox::create([
                          FlexText::create('Q1. 我有個問題問題問題問題？？'),
                          FlexBox::create([
                            FlexText::create('處理中...')->setSize('xxs')->setAlign('start')->setColor('#f37370'),
                            FlexText::create('2018-08-08')->setSize('xxs')->setAlign('end')->setColor('#f37370')
                          ])->setLayout('horizontal')->setMargin('lg')
                        ])->setLayout('vertical')
                      ])->setLayout('vertical')->setFlex(7),
                      FlexSeparator::create(),
                      FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換', '您已按了切換', '123'))
                    ])->setLayout('horizontal')->setSpacing('md'),
                    FlexSeparator::create(),

                    FlexBox::create([
                      FlexBox::create([
                        FlexBox::create([
                          FlexText::create('Q1. 我有個問題問題問題問題？？'),
                          FlexBox::create([
                            FlexText::create('處理中..')->setSize('xxs')->setAlign('start')->setColor('#f37370'),
                            FlexText::create('2018-08-08')->setSize('xxs')->setAlign('end')->setColor('#f37370')
                          ])->setLayout('horizontal')->setMargin('lg')
                        ])->setLayout('vertical')
                      ])->setLayout('vertical')->setFlex(7),
                      FlexSeparator::create(),
                      FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換', '您已按了切換', '123'))
                    ])->setLayout('horizontal')->setSpacing('md'),

                    FlexSeparator::create(),

                    FlexText::create('發票問題')->setColor('#12776e'),
                    FlexSeparator::create(),

                    FlexBox::create([
                      FlexBox::create([
                        FlexBox::create([
                          FlexText::create('Q3. 我有個問題問題問題問題？？'),
                          FlexBox::create([
                            FlexText::create('待處理')->setSize('xxs')->setAlign('start')->setColor('#bbbbbb'),
                            FlexText::create('2018-08-08')->setSize('xxs')->setAlign('end')->setColor('#f37370')
                          ])->setLayout('horizontal')->setMargin('lg')
                        ])->setLayout('vertical')
                      ])->setLayout('vertical')->setFlex(7),
                      FlexSeparator::create(),
                      FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換', '您已按了切換', '123'))
                    ])->setLayout('horizontal')->setSpacing('md'),
                    FlexSeparator::create(),
                  ])->setLayout('vertical')->setSpacing('md')->setMargin('sm'),
                'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
              ])); 

       
              break;
            case 2:
              break;
            case 3:
              break;
          }

          $msg->reply($event->getReplyToken());

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
