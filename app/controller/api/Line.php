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
    print_r(RichMenuGenerator::create());
          die;
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

          prinr_r(RichMenuGenerator::create());
          die;
          // $msg = MyLineBotMsg::create()->flex('開始在下方輸入問題內容', FlexBubble::create([
          //   'header' => FlexBox::create([FlexText::create('開始在下方輸入問題內容')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
          //   'body' => FlexBox::create([FlexBox::create([FlexButton::create('primary')->setColor('#f97172')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('回覆訊息後按此送出', null, '123'))])->setLayout('vertical')->setMargin('xxl')->setSpacing('sm')])->setLayout('vertical'),
          //   'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
          // ]))->reply($event->getReplyToken());

          // switch($log->text) {
          //   case 1:
          //     $msg = MyLineBotMsg::create()->flex('問題列表 - 正在進行中', FlexBubble::create([
          //       'header' => FlexBox::create([ FlexText::create('問題列表 - 正在進行中')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
          //       'body' => FlexBox::create([
                  
          //           FlexText::create('付款問題')->setColor('#12776e')->setWeight('bold'),
          //           FlexSeparator::create(),

          //           FlexBox::create([
          //             FlexBox::create([FlexBox::create([
          //               FlexText::create('Q1. 我有個問題問題問題問題？？'),
          //               FlexBox::create([FlexText::create('處理中...')->setSize('xxs')->setAlign('start')->setColor('#f37370'), FlexText::create('2018-08-08')->setSize('xxs')->setAlign('end')->setColor('#bbbbbb')])->setLayout('horizontal')->setMargin('lg')
          //             ])->setLayout('vertical')])->setLayout('vertical')->setFlex(7),
          //             FlexSeparator::create(),
          //             FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換', '您已按了切換', '123'))
          //           ])->setLayout('horizontal')->setSpacing('md'),
          //           FlexSeparator::create(),

          //           FlexBox::create([
          //             FlexBox::create([FlexBox::create([
          //               FlexText::create('Q2. 我有個問題問題問題問題？？'),
          //               FlexBox::create([ FlexText::create('處理中...')->setSize('xxs')->setAlign('start')->setColor('#f37370'), FlexText::create('2018-08-08')->setSize('xxs')->setAlign('end')->setColor('#bbbbbb')])->setLayout('horizontal')->setMargin('lg')
          //             ])->setLayout('vertical')])->setLayout('vertical')->setFlex(7),
          //             FlexSeparator::create(),
          //             FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換', '您已按了切換', '123'))
          //           ])->setLayout('horizontal')->setSpacing('md'),
          //           FlexSeparator::create(),

          //           FlexText::create('發票問題')->setColor('#12776e')->setWeight('bold'),
          //           FlexSeparator::create(),

          //           FlexBox::create([
          //             FlexBox::create([FlexBox::create([
          //               FlexText::create('Q3. 我有個問題問題問題問題？？'),
          //               FlexBox::create([FlexText::create('待處理')->setSize('xxs')->setAlign('start')->setColor('#bbbbbb'), FlexText::create('2018-08-08')->setSize('xxs')->setAlign('end')->setColor('#bbbbbb')])->setLayout('horizontal')->setMargin('lg')])->setLayout('vertical')
          //             ])->setLayout('vertical')->setFlex(7),
          //             FlexSeparator::create(),
          //             FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換', '您已按了切換', '123'))
          //           ])->setLayout('horizontal')->setSpacing('md'),
          //           FlexSeparator::create(),

          //         ])->setLayout('vertical')->setSpacing('md')->setMargin('sm'),
          //       'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
          //     ])); 

       
          //     break;
          //   case 2:
          //     $msg = MyLineBotMsg::create()->flex('已切換問題', FlexBubble::create([
          //       'header' => FlexBox::create([FlexText::create('已切換問題')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2')])->setSpacing('xs')->setLayout('horizontal'),
          //       'body' => FlexBox::create([
          //         FlexText::create('Q: 我的問題有好多好多？')->setWeight('bold')->setColor('#307671'),
          //         FlexSeparator::create()->setMargin('xxl'),
          //         FlexBox::create([
          //           FlexButton::create('primary')->setColor('#fbd785')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('檢視先前的對話紀錄', '查看對話紀錄', '123')),
          //           FlexButton::create('primary')->setColor('#f97172')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('回覆訊息後按此送出', '送出訊息', '123'))
          //         ])->setLayout('vertical')->setMargin('xxl')->setSpacing('sm')
          //       ])->setLayout('vertical'),
          //       'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
          //     ]));
          //     break;
          //   case 3:
          //     $msg = MyLineBotMsg::create()->flex('檢視問題內容', FlexBubble::create([
          //       'header' => FlexBox::create([FlexText::create('Q1.我的問題是xxxxxxxxxxxxxxxxxxxx')->setWeight('bold')->setSize('md')->setColor('#e8f6f2')])->setSpacing('xs')->setLayout('horizontal'),
          //       'body' => FlexBox::create([
          //           FlexText::create('最近更新時間：2018-08-08 13:45:23')->setColor('#aaaaaa')->setSize('xxs')->setAlign('start'),
          //           FlexText::create('貨到缺件反應啦啦啦阿拉啦啦啦')->setSize('xs'),
          //           FlexSeparator::create()->setMargin('xxl'),

          //           FlexText::create('Re: 2018-07-19 17:23:39')->setSize('xs')->setWeight('bold'),
          //           FlexText::create('沒問題的啦啦啦阿拉啦啦啦')->setSize('xs'),
          //           FlexSeparator::create()->setMargin('xxl'),

          //           FlexText::create('貨到缺件反應啦啦啦阿拉啦啦啦')->setSize('xs'),
          //           FlexText::create('貨到缺件反應啦啦啦阿拉啦啦啦')->setSize('xs'),
          //           FlexSeparator::create()->setMargin('xxl'),

          //           FlexText::create('Re: 2018-07-19 17:23:39')->setSize('xs')->setWeight('bold'),
          //           FlexText::create('沒問題的啦啦啦阿拉啦啦啦')->setSize('xs'),

          //           FlexSeparator::create()->setMargin('xxl'),
          //           FlexButton::create('primary')->setColor('#f97172')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('回覆訊息後按此送出', '送出訊息', '123'))
                 
          //         ])->setLayout('vertical')->setSpacing('md'),
          //       'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
          //     ]));
          //     break;
          // }
         
          // $msg->reply($event->getReplyToken());

          // die;
          $pattern = 'hello';
          $pattern = !preg_match ('/\(\?P<k>.+\)/', $pattern) ? '/(?P<k>(' . $pattern . '))/i' : ('/(' . $pattern . ')/i');
          preg_match_all ($pattern, $log->text, $result);
 
          //傳送hello時跳出開始menu
          if ($result['k'] && $msg = LineTool::start() )
            $msg->reply($event->getReplyToken());
         
          //檢查Source process是否非空，是則新增進去
          if(!empty($source->process))
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
          //暫時修正
          if( isset($data['class']) ) {
            if( !( isset( $data['lib'], $data['method'] ) && ( isset( self::$cache['lib'][$data['lib']] ) ? true : ( Load::lib($data['lib'] . '.php') ? self::$cache['lib'][$data['lib']] = true : true ) )
              && method_exists($class = $data['class'], $method = $data['method']) && $msg = $class::$method( $data['param'], $source ) ) )
              return false;
          }
          else if ( !( isset( $data['lib'], $data['method'] ) && ( isset( self::$cache['lib'][$data['lib']] ) ? true : ( Load::lib($data['lib'] . '.php') ? self::$cache['lib'][$data['lib']] = true : true ) )
               && method_exists($lib = $data['lib'], $method = $data['method']) && $msg = $lib::$method( $data['param'], $source ) ) )
            return false;

          $msg->reply($event->getReplyToken());
          break;
      }
    }
  }

}
