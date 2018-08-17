<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OC Wu <cherry51120@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

use LINE\LINEBot;
use LINE\LINEBot\Constant;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Constant\Meta;
use LINE\LINEBot\HTTPClient;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;

use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\TemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\DatetimePickerTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapMessageActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;

use LINE\LINEBot\RichMenuBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuSizeBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBoundsBuilder;

use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\MessageEvent\VideoMessage;
use LINE\LINEBot\Event\MessageEvent\StickerMessage;
use LINE\LINEBot\Event\MessageEvent\LocationMessage;
use LINE\LINEBot\Event\MessageEvent\ImageMessage;
use LINE\LINEBot\Event\MessageEvent\AudioMessage;
use LINE\LINEBot\Event\MessageEvent\FileMessage;

use LINE\LINEBot\Event\JoinEvent;
use LINE\LINEBot\Event\LeaveEvent;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use LINE\LINEBot\Event\PostbackEvent;

class MyLineBot extends LINEBot{
  static $bot;

  public function __construct ($client, $option) {
    parent::__construct ($client, $option);
  }
  public static function create() {
    return new LINEBot( new CurlHTTPClient(config('line', 'channelToken')), ['channelSecret' => config('line', 'channelSecret')]);
  }
  public static function bot() {
    if (self::$bot)
      return self::$bot;

    return self::$bot = self::create ();
  }
  public static function events() {
    if( !isset ($_SERVER["HTTP_" . HTTPHeader::LINE_SIGNATURE]) )
      return false;

    try {
      // Log::info( file_get_contents ("php://input") );
      return MyLineBot::bot()->parseEventRequest (file_get_contents ("php://input"), $_SERVER["HTTP_" . HTTPHeader::LINE_SIGNATURE]);
    } catch (Exception $e) {
      return $e;
    }
  }
}

class MyLineBotLog {
  private $source, $speaker, $event, $param;

  public function __construct($source, $speaker, $event) {
    $this->source = $source;
    $this->speaker = $speaker;
    $this->event = $event;
    $this->param = [];
  }

  public static function init( $source, $speaker, $event ) {
    return new MyLineBotLog($source, $speaker, $event);
  }

  public function create() {
    if( $this->event->getType() == 'message' )
      $this->setMessageParam();

    $class = get_class($this->event);
    $func = lcfirst( substr($class, ($pos = strripos($class, '\\')) + 1, strlen($class) - $pos ) );

    if( method_exists(__CLASS__, $func) )
      return $this->$func();
    return false;
  }

  public function setMessageParam() {
    $this->param = [
      'source_id' => $this->source->id,
      'speaker_id' => $this->speaker->id,
      'reply_token' => $this->event->getReplyToken() ? $this->event->getReplyToken() : '',
      'message_id' => $this->event->getMessageId() ? $this->event->getMessageId() : '',
      'timestamp' => $this->event->getTimestamp() ? $this->event->getTimestamp() : '',
    ];
    return $this;
  }

  private function textMessage() {
    $param = array_merge( $this->param, array('text' => $this->event->getText()) );
    if( !Text::transaction( function() use ($param, &$obj) { return $obj = Text::create($param); }) )
      return false;
    return $obj;
  }

  private function imageMessage() {
    if ( !$obj = MyLineBot::bot()->getMessageContent( $this->event->getMessageId() ) )
      return false;
    if ( !$obj->isSucceeded() )
      return false;

    $param = array_merge( $this->param, array('file' => '') );
    $filename = FCPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid( rand() . '_' ) . get_extension_by_mime( $obj->getHeader('Content-Type') );

    if ( !(write_file( $filename, $obj->getRawBody()) && $image = Image::create($param) ) )
      return false;
    if( !$image->file->put($filename) )
      return false;

    return $image;
  }

  private function videoMessage() {
    if ( !$obj = MyLineBot::bot()->getMessageContent( $this->event->getMessageId() ) )
      return false;
    if ( !$obj->isSucceeded() )
      return false;

    $param = array_merge( $this->param, array('file' => '') );
    $filename = FCPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid( rand() . '_' ) . get_extension_by_mime( $obj->getHeader('Content-Type') );

    if ( !(write_file( $filename, $obj->getRawBody()) && $video = Video::create($param) ) )
      return false;
    if( !$video->file->put($filename) )
      return false;
    return $video;
  }

  private function audioMessage() {
    if ( !$obj = MyLineBot::bot()->getMessageContent( $this->event->getMessageId() ) )
      return false;
    if ( !$obj->isSucceeded() )
      return false;

    $param = array_merge( $this->param, array('file' => '') );
    $filename = FCPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid( rand() . '_' ) . get_extension_by_mime( $obj->getHeader('Content-Type') );

    if ( !(write_file( $filename, $obj->getRawBody()) && $audio = Audio::create($param) ) )
      return false;

    if( !$audio->file->put($filename) )
      return false;
    return $audio;
  }

  private function fileMessage() {
    $param = array_merge( $this->param, array('text' => $this->event->getText()) );
    return Text::transaction( function() use ($param) {
      return Text::create($param);
    });
  }

  private function locationMessage() {
    $param = array_merge( $this->param, array( 'title' =>  $this->event->getTitle(), 'address' =>  $this->event->getAddress(), 'latitude' =>  $this->event->getLatitude(), 'longitude' =>  $this->event->getLongitude(), ));
    if( !Location::transaction(function ($param, &$obj) { return $obj = Location::create($param);}, $param, $obj) ) {
      return false;
    }
    return $obj;
  }

  private function followEvent() {
    $param = array( 'source_id' => $this->source->id, 'reply_token' => $this->event->getReplyToken() ? $this->event->getReplyToken() : '', 'timestamp' => $this->event->getTimestamp() ? $this->event->getTimestamp() : '');
    if( !Follow::transaction( function($param, &$obj) { return $obj = Follow::create($param); }, $param, $obj ) )
      return false;
    return $obj;
  }

  private function unfollowEvent() {
    $param = array( 'source_id' => $this->source->id, 'timestamp' => $this->event->getTimestamp() );
    if( !Unfollow::transaction( function($param, &$obj) { return $obj = Unfollow::create($param); }, $param, $obj ))
      return false;
    return $obj;
  }

  private function joinEvent() {
    $param = array( 'source_id' => $this->source->id, 'reply_token' => $this->event->getReplyToken(), 'timestamp' => $this->event->getTimestamp() );
    if( !Join::transaction( function($param, &$obj) { return $obj = Join::create($param); }, $param, $obj ))
      return false;
    return $obj;
  }

  private function leaveEvent() {
    $param = array( 'source_id' => $this->source->id, 'timestamp' => $this->event->getTimestamp() );
    if( !Leave::transaction( function($param, &$obj) { return $obj = Leave::create($param); }, $param, $obj ))
      return false;
    return $obj;
  }

  private function postbackEvent() {
    $param = array( 'source_id' => $this->source->id, 'speaker_id' => $this->speaker->id, 'reply_token' => $this->event->getReplyToken(), 'data' => is_array($this->event->getPostbackData())? json_encode($this->event->getPostbackData()) : $this->event->getPostbackData(), 'params' => $this->event->getPostbackParams() ? json_encode($this->event->getPostbackParams()):'', 'timestamp' => $this->event->getTimestamp());
    if( !Postback::transaction( function($param, &$obj) { return $obj = Postback::create($param); }, $param, $obj ))
      return false;
    return $obj;
  }
}

class MyLineBotMsg {
  public $builder;

  public function __construct() {
  }
  public static function create() {
    return new MyLineBotMsg();
  }
  public function reply ($token) {
    if ($this->builder)
      MyLineBot::bot()->replyMessage($token, $this->builder);
  }
  public function getBuilder() {
    return $this->builder;
  }
  public function text($text) {
    $this->builder = !is_null($text) ? new TextMessageBuilder($text) : null;
    return $this;
  }
  public function image($url1, $url2) {
    $this->builder = is_string ($url1) && is_string ($url2) ? new ImageMessageBuilder($url1, $url2) : null;
    return $this;
  }
  public function sticker($packId, $id) {
    $this->builder = is_numeric($packId) && is_numeric($id) ? new StickerMessageBuilder($packId, $id) : null;
    return $this;
  }
  public function video($ori, $prev) {
    $this->builder = isHttps($ori) && isHttps($prev) ? new VideoMessageBuilder($ori, $prev) : null;
    return $this;
  }
  public function audio($ori, $d) {
    $this->builder = isHttps($ori) && is_numeric($d) ? new AudioMessageBuilder($ori, $d) : null;
    return $this;
  }
  public function location($title, $add, $lat, $lon) {
    $this->builder = is_string($title) && is_string($add) && is_numeric($lat) && is_numeric($lon) ? new LocationMessageBuilder($title, $add, $lat, $lon) : null;
    return $this;
  }
  public function imagemap($url, $altText, $weight, $height, array $actionBuilders) {
    $this->builder = isHttps($url) && is_string($altText) && is_numeric($weight) && is_numeric($height) && is_array($actionBuilders) ? new ImagemapMessageBuilder($url, $altText, new BaseSizeBuilder($height, $weight), $actionBuilders) : null;
    return $this;
  }
  public function multi($builds) {
    if (!is_array ($builds))
      $this->builder = null;

    $this->builder = new MultiMessageBuilder();
    foreach ($builds as $build) {
      $this->builder->add ($build->getBuilder ());
    }
    return $this;
  }
  public function templateConfirm($text, array $actionBuilders) {
    $this->builder = is_string($text) && is_array($actionBuilders) ? new ConfirmTemplateBuilder($text, $actionBuilders) : null;
    return $this;
  }
  public function templateImageCarousel(array $columnBuilders) {
    $this->builder = is_array($columnBuilders) ? new ImageCarouselTemplateBuilder($columnBuilders) : null;
    return $this;
  }
  public function templateImageCarouselColumn($imageUrl, $actionBuilder) {
    return is_string($imageUrl) && is_object($actionBuilder) ? new ImageCarouselColumnTemplateBuilder($imageUrl, $actionBuilder) : null;
  }
  public function templateButton($title, $text, $imageUrl, array $actionBuilders) {
    $this->builder = is_string($title) && is_string($text) && is_string($imageUrl) && is_array($actionBuilders) ? new ButtonTemplateBuilder($title, $text, $imageUrl, $actionBuilders) : null;
    return $this;
  }
  public function templateCarouselColumn($title, $text, $imageUrl, array $actionBuilders) {
    return is_string($title) && is_string($text) && is_array($actionBuilders) ? new CarouselColumnTemplateBuilder($title, $text, $imageUrl, $actionBuilders) : null;
  }
  public function templateCarousel(array $columnBuilders) {
    $this->builder = is_array($columnBuilders) ? new CarouselTemplateBuilder($columnBuilders) : null;
    return $this;
  }
  public function template($text, $builder) {
    if( !is_string($text) || empty($builder) )
      return $this;

    $this->builder = new TemplateMessageBuilder($text, $builder->getBuilder());
    return $this;
  }

  public function flex($text, $builder) {
    if( empty($builder) )
      return $this;

    $this->builder = new FlexMessageBuilder($text, $builder);
    return $this;
  }

  public function flexBubbleBuilder() {
    return FlexBubbleBuilder::create();
  }

  public function flexCarouselBuilder(array $bubbleBuilder) {
    return new FlexCarouselBuilder($bubbleBuilder);
  }

}

class MyLineBotActionMsg {
  private $action;
  public function __construct() {
  }
  public static function create() {
    return new MyLineBotActionMsg();
  }
  public function datetimePicker($label, $data, $mode, $initial = null, $max = null, $min = null) {
    return is_string($label) && is_string($data) && in_array($mode, ['date', 'time', 'datetime']) ? new DatetimePickerTemplateActionBuilder($label, $data, $mode, $initial, $max, $min) : null;
  }
  public function message($label, $text) {
    return is_string($label) && is_string($text) ? new MessageTemplateActionBuilder($label, $text) : null;
  }
  public function uri($label, $url) {
    return is_string($label) ? new UriTemplateActionBuilder($label, $url) : null;
  }
  public function postback($label, $data, $text = null) {
    return is_string($label) && ($data = is_array($data) ? json_encode($data) : $data ) ? new PostbackTemplateActionBuilder($label, $data, null) : null;
  }

  public function imagemapMsg($text, $x, $y, $width, $height) {
    return is_string($text) && is_numeric($x) && is_numeric($y) && is_numeric($width) && is_numeric($height) ? new ImagemapMessageActionBuilder($text, new AreaBuilder($x, $y, $width, $height) ) : null;
  }
  public function imagemapUri($url, $x, $y, $width, $height) {
    return is_string($url) && is_numeric($x) && is_numeric($y) && is_numeric($width) && is_numeric($height) ? new ImagemapUriActionBuilder($url, new AreaBuilder($x, $y, $width, $height) ) : null;
  }
}

class FlexMessageBuilder implements MessageBuilder {
  private $type;
  private $altText = '';
  private $contentBuilder;

  public function __construct($altText, FlexContentBuilder $contentBuilder) {
    $this->type = 'flex';
    $this->altText = $altText;
    $this->contentBuilder = $contentBuilder;
  }

  public function buildMessage() {
    return [
      [
        'type' => $this->type,
        'altText' => $this->altText,
        'contents' => $this->contentBuilder->attrs(),
      ]
    ];
  }
}

interface FlexContentBuilder {
  public function build($objs);
}

class FlexBubble implements FlexContentBuilder {
  public $flexAttrs = [];
  public function __construct(array $objs) {
    $this->flexAttrs['type'] = 'bubble';
    $this->build($objs);
  }
  public static function create(array $objs) {
    return new FlexBubble($objs);
  }
  public static function objsRecursiveToArray($objs) {
    return is_array($objs) ? array_map(function($obj) {
      return is_object($obj) ? array_map('self::objsRecursiveToArray', $obj->attrs()) : $obj;
    }, $objs) : $objs;
  }
  public function build($objs) {
    !$objs && gg('bubble 傳入參數需為陣列');
    $this->flexAttrs = array_merge($this->flexAttrs, self::objsRecursiveToArray($objs));
    return $this;
  }
  public function attrs() {
    return $this->flexAttrs;
  }
}

class FlexCarousel implements FlexContentBuilder {
  public $flexAttrs = [];
  public function __construct(array $bubbles) {
    $this->flexAttrs['type'] = 'carousel';
    $this->build($bubbles);
  }
  public static function create(array $bubbles) {
    return new FlexCarousel($bubbles);
  }
  public function build($bubbles) {
    !$bubbles && gg('Carousel 傳入Bubble參數需為陣列');
    foreach($bubbles as $bubble) {
      $this->flexAttrs['contents'][] = $bubble->flexAttrs;
    }
    return $this;
  }
  public function attrs() {
    return $this->flexAttrs;
  }
}

abstract class FlexComponents {
  protected $attrs = [];
  public function __construct() {}
  public function attrs() {
    return $this->attrs;
  }
}

class FlexStyles extends FlexComponents {
  public function __construct() {
    parent::__construct();
  }
  public static function create() {
    return new FlexStyles();
  }
  public function setHeader($value) {
    $this->attrs['header'] = $value->attrs();
    return $this;
  }
  public function setBody($value) {
    $this->attrs['body'] = $value->attrs();
    return $this;
  }
  public function setFooter($value) {
    $this->attrs['footer'] = $value->attrs();
    return $this;
  }
  public function setHero($values) {
    $this->attrs['hero'] = $value->attrs();
    return $this;
  }
}

class FlexBlock extends FlexComponents {
  public static function create() {
    return new FlexBlock;
  }
  public function setBackgroundColor($value) {
    $this->attrs['backgroundColor'] = $value;
    return $this;
  }
  public function setSeparator($value) {
    $this->attrs['separator'] = $value;
    return $this;
  }
  public function setSeparatorColor($value) {
    $this->attrs['separatorColor'] = $value;
    return $this;
  }
}

class FlexBox extends FlexComponents{
  public function __construct(array $contents) {
    parent::__construct();
    $this->attrs['type'] = 'box';
    $this->setContents($contents);
  }
  public static function create( $contents ) {
    return new FlexBox($contents);
  }
  public function setLayout($value) {
    if(is_string($value)) $this->attrs['layout'] = $value;
    return $this;
  }
  public function setContents(array $contents) {
    $this->attrs['contents'] = $contents;
    return $this;
  }
  public function setSpacing($value) {
    if(is_string($value)) $this->attrs['spacing'] = $value;
    return $this;
  }
  public function setFlex($value) {
    if(is_numeric($value)) $this->attrs['flex'] = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->attrs['margin'] = $value;
    return $this;
  }
}
class FlexButton extends FlexComponents {
  public function __construct($style) {
    $this->attrs['type'] = 'button';
    $this->setStyle($style);
  }
  public static function create($style) {
    return new FlexButton($style);
  }
  public function setAction($action) {
    $this->attrs['action'] = $action;
    return $this;
  }
  public function setFlex($value) {
    if(is_numeric($value)) $this->attrs['flex'] = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->attrs['margin'] = $value;
    return $this;
  }
  public function setHeight($value) {
    if(is_string($value)) $this->attrs['height'] = $value;
    return $this;
  }
  public function setStyle($value) {
    if(is_string($value)) $this->attrs['style'] = $value;
    return $this;
  }
  public function setColor($value) {
    if(is_string($value)) $this->attrs['color'] = $value;
    return $this;
  }
  public function setGravity($value) {
    if(is_string($value)) $this->attrs['gravity'] = $value;
    return $this;
  }
}
class FlexIcon extends FlexComponents{
  public function __construct($url) {
    parent::__construct();
    $this->attrs['type'] = 'icon';
    $this->setUrl($url);
  }
  public static function create($url) {
    return new FlexIcon($url);
  }
  public function setUrl($value) {
    if(is_string($value)) $this->attrs['url'] = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->attrs['margin'] = $value;
    return $this;
  }
  public function setSize($value) {
    if(is_string($value)) $this->attrs['size'] = $value;
    return $this;
  }
  public function setAspectRatio($value) {
    if(is_string($value)) $this->attrs['aspectRatio'] = $value;
    return $this;
  }
}
class FlexImage extends FlexComponents{
  public function __construct($url) {
    $this->attrs['type'] = 'image';
    $this->setUrl($url);
  }
  public static function create($url) {
    return new FlexImage($url);
  }
  public function setUrl($value) {
    if(is_string($value)) $this->attrs['url'] = $value;
    return $this;
  }
  public function setFlex($value) {
    if(is_numeric($value)) $this->attrs['flex'] = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->attrs['margin'] = $value;
    return $this;
  }
  public function setAlign($value) {
    if(is_string($value)) $this->attrs['align'] = $value;
    return $this;
  }
  public function setGravity($value) {
    if(is_string($value)) $this->attrs['gravity'] = $value;
    return $this;
  }
  public function setSize($value) {
    if(is_string($value)) $this->attrs['size'] = $value;
    return $this;
  }
  public function setAspectRatio($value) {
    if(is_string($value)) $this->attrs['aspectRatio'] = $value;
    return $this;
  }
  public function setAspectMode($value) {
    if(is_string($value)) $this->attrs['aspectMode'] = $value;
    return $this;
  }
  public function setBackgroundColor($value) {
    if(is_string($value)) $this->attrs['backgroundColor'] = $value;
    return $this;
  }
  public function setAction() {

  }
}
class FlexSeparator extends FlexComponents{
  public function __construct() {
    parent::__construct();
    $this->attrs['type'] = 'separator';
  }
  public static function create() {
    return new FlexSeparator();
  }
  public function setMargin($value) {
    if(is_string($value)) $this->attrs['margin'] = $value;
    return $this;
  }
  public function setColor($value) {
    if(is_string($value)) $this->attrs['color'] = $value;
    return $this;
  }
}
class FlexSpacer extends FlexComponents{
  public function __construct($size) {
    parent::__construct();
    $this->attrs['type'] = 'spacer';
    $this->setSize($size);
  }
  public static function create($size) {
    return new FlexSpacer($size);
  }
  public function setSize($value) {
    if(is_string($value)) $this->attrs['size'] = $value;
    return $this;
  }
}
class FlexText extends FlexComponents {
  public function __construct($text) {
    parent::__construct();
    $this->attrs['type'] = 'text';
    $this->setText($text);
  }
  public static function create($text) {
    return new FlexText($text);
  }
  public function setText($value) {
    if(is_string($value)) $this->attrs['text'] = $value;
    return $this;
  }
  public function setFlex($value) {
    if(is_numeric($value)) $this->attrs['flex'] = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->attrs['margin'] = $value;
    return $this;
  }
  public function setSize($value) {
    if(is_string($value)) $this->attrs['size'] = $value;
    return $this;
  }
  public function setAlign($value) {
    if(is_string($value)) $this->attrs['align'] = $value;
    return $this;
  }
  public function setGravity($value) {
    if(is_string($value)) $this->attrs['gravity'] = $value;
    return $this;
  }
  public function setWrap($value) {
    if(is_string($value)) $this->attrs['wrap'] = $value;
    return $this;
  }
  public function setWeight($value) {
    if(is_string($value)) $this->attrs['weight'] = $value;
    return $this;
  }
  public function setColor($value) {
    if(is_string($value)) $this->attrs['color'] = $value;
    return $this;
  }
  public function setAction($value) {
    $this->attrs['action'] = $value;
    return $this;
  }

}

class FlexAction {
  public static function postBack($label, $text, $data) {
    return is_string($label) && is_string($text) && is_array($data) ? json_encode($data) : $data ? [ 'type' => 'postback', 'label' => $label, 'data' => $data, 'text' => null ] : null;
  }
  public static function message($label, $text) {
    return is_string($label) && is_string($text) ? [ 'type' => 'message', 'label' => $label, 'text' => $text ] : null;
  }
  public static function uri($label, $uri) {
    return is_string($label) && is_string($uri) ? [ 'type' => 'uri', 'label' => $label, 'uri' => $uri ] : null;
  }
  public static function datetimepicker($label, $data, $mode, $initial = null, $max = null, $min = null) {
    return is_string($label) && is_string($data) && in_array($mode, ['date', 'time', 'datetime']) ? ['type' => 'datetimepicker', 'label' => $label, 'data' => $data, 'mode' => $mode, 'initial' => $initial, 'max' => $max, 'min' => $min ] : null;
  }
}

class RichMenuGenerator {
  public static function create() {

    if($lists = RichMenu::getMenuList() && isset($lists['richmenus'])) 
      foreach($lists['richmenus'] as $list) 
        if(!RichMenu::delete($list['richMenuId']))
          return false;

    if(!$richMenuId = RichMenu::create(BuildRichMenu::create(
                  BuildRichMenu::size(843), false, '客服系統', '更多',
                  [ 
                    BuildRichMenu::area(BuildRichMenu::areaBound(0, 0, 833, 843), MyLineBotActionMsg::create()->postback('您已點擊正在進行中的問題', json_encode( ['lib' => 'postback/RichMenu', 'class' => 'Qa', 'method' => 'create', 'param' => [] ]), '您已點擊正在進行中的問題')),
                    BuildRichMenu::area(BuildRichMenu::areaBound(834, 0, 833, 843), MyLineBotActionMsg::create()->postback('您已點擊首頁', json_encode( ['lib' => 'postback/RichMenu', 'class' => 'Menu', 'method' => 'create', 'param' => [] ]), '您已點擊首頁')),
                    BuildRichMenu::area(BuildRichMenu::areaBound(1668, 0, 833, 843), MyLineBotActionMsg::create()->postback('您已點擊意見回饋', json_encode( ['lib' => 'postback/RichMenu', 'class' => 'Contact', 'method' => 'create', 'param' => [] ]), '您已點擊意見回饋')),
                  ]
    )))
      return false;

    if(!$img = RichMenu::uploadImage($richMenuId, '/Users/wu-tzu-hsuan/www/customerService/assets/img/menu_v1.png', 'image/png'))
      return false;

    if($unlink = RichMenu::unlinkToUser('Uef2e17250863e4724e74578bd34ed333') && !RichMenu::linkToUser('Uef2e17250863e4724e74578bd34ed333', $richMenuId))
      return false;
    return true;
  }
}

class RichMenu {
  public static function create($richMenuBuilder) {
    return ($res = MyLineBot::bot()->createRichMenu($richMenuBuilder)) && $res->isSucceeded() ? $res->getJSONDecodedBody()['richMenuId'] : false;
  }
  public static function delete($richMenuId) {
    return ($res = MyLineBot::bot()->deleteRichMenu($richMenuId)) && $res->isSucceeded() ? true : false;
  }
  public static function getMenuId($userId) {
    return ($res = MyLineBot::bot()->getRichMenuId($userId)) && $res->isSucceeded() ? $res->getJSONDecodedBody() : false;
  }
  public static function linkToUser($userId, $richMenuId) {
    return ($res = MyLineBot::bot()->linkRichMenu($userId, $richMenuId)) && $res->isSucceeded() ? true : false;
  }
  public static function unlinkToUser($userId) {
    return ($res = MyLineBot::bot()->unlinkRichMenu($userId)) && $res->isSucceeded() ? true : false;
  }
  public static function downloadImage($richMenuId) {
    return ($res = MyLineBot::bot()->downloadRichMenuImage($richMenuId)) && $res->isSucceeded() ? true : false;
  }
  public static function uploadImage($richMenuId, $imagePath, $contentType) {
    return ($res = MyLineBot::bot()->uploadRichMenuImage($richMenuId, $imagePath, $contentType)) && $res->isSucceeded() ? true : false;
  }
  public static function getMenuList() {
    return ($res = MyLineBot::bot()->getRichMenuList()) && $res->isSucceeded() ? $res->getJSONDecodedBody() : [];
  }

}

class BuildRichMenu {
  public static function create($sizeBuilder, $selected, $name, $chartBarText, $areaBuilders) {
    return new RichMenuBuilder($sizeBuilder, $selected, $name, $chartBarText, $areaBuilders);
  }
  /**
   * @param int $height Height of the rich menu. Possible values: 1686, 843.
   * @param int $width Width of the rich menu. Must be 2500.
   */
  public static function size($height, $width = 2500) {
    return new RichMenuSizeBuilder($height, $width);
  }
  /**
   * @param RichMenuAreaBoundsBuilder $boundsBuilder 
   * @param MyLineBotActionMsg $actionBuilder
   */
  public static function area($boundsBuilder, $actionBuilder) {
    return new RichMenuAreaBuilder($boundsBuilder, $actionBuilder);
  }
  public static function areaBound($x, $y, $width, $height) {
    return new RichMenuAreaBoundsBuilder($x, $y, $width, $height);
  }
}