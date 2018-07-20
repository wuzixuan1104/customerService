<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */
 use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
 use LINE\LINEBot\MessageBuilder\BubbleBuilder;
 use LINE\LINEBot\MessageBuilder\FlexComponent;

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

          Load::lib('FlexMessageBuilder.php');
          $builder = new FlexMessageBuilder('altText',
                  BubbleBuilder::create()
                  ->setHeader( FlexComponent::create()->setType('box')->setLayout('vertical')->setContents([
                    FlexComponent::create()->setType('text')->setText('Header text')])->format()
                  )->setBody( FlexComponent::create()->setType('box')->setLayout('vertical')->setContents([
                    FlexComponent::create()->setType('text')->setText('Body text')])->format()
                  )->setFooter( FlexComponent::create()->setType('box')->setLayout('vertical')->setContents([
                    FlexComponent::create()->setType('text')->setText('Footer text')])->format()
                  )->setHero( FlexComponent::create()->setType('image')->setUrl('https://example.com/flex/images/image.jpg')->format()
                )->buildContent() );

          Log::info('a:' . json_encode($builder));

          // $builder = MyLineBotMsg::create()->template('這訊息要用手機的賴才看的到哦',
          //     MyLineBotMsg::create()->templateCarousel( [
          //       MyLineBotMsg::create()->templateCarouselColumn('標題', '哈哈哈哈哈', 'https://cdn.adpost.com.tw/adpost/production/uploads/adv_details/pic/00/00/00/00/00/00/06/5e/_29753e27ceb64b0f35b77aca7acf9a3e.jpg', [
          //         MyLineBotActionMsg::create()->datetimePicker('date', date('Y-m-d'), 'date', '', '', ''),
          //         // MyLineBotActionMsg::create()->message('label', 'test'),
          //         MyLineBotActionMsg::create()->uri("Google","http://www.google.com"),
          //         MyLineBotActionMsg::create()->postback('label', 'postback', 'postback'),
          //       ]),
          //       MyLineBotMsg::create()->templateCarouselColumn('標題', '哈哈哈哈哈', 'https://cdn.adpost.com.tw/adpost/production/uploads/adv_details/pic/00/00/00/00/00/00/06/5e/_29753e27ceb64b0f35b77aca7acf9a3e.jpg', [
          //         MyLineBotActionMsg::create()->datetimePicker('date', date('Y-m-d'), 'date', '', '', ''),
          //         MyLineBotActionMsg::create()->message('label', 'test'),
          //         // MyLineBotActionMsg::create()->uri("Google","http://www.google.com"),
          //         MyLineBotActionMsg::create()->postback('label', 'postback', 'postback'),
          //       ]),
          //     ])
          // );

          // print_r($builder);
          // die;
          MyLineBot::bot()->replyMessage($event->getReplyToken(), $builder);
          Log::info('end');
          // print_r($builder);
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
