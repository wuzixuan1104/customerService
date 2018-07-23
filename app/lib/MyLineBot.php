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
  public function postback($label, $data, $text) {
    return is_string($label) && ($data = is_array($data) ? json_encode($data) : $data ) && !is_null($text) ? new PostbackTemplateActionBuilder($label, $data, $text) : null;
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

  public function __construct($altText, ContentBuilder $contentBuilder) {
    $this->type = 'flex';
    $this->altText = $altText;
    $this->contentBuilder = $contentBuilder;
  }

  public function buildMessage() {
    return [
      [
        'type' => $this->type,
        'altText' => $this->altText,
        'contents' => $this->contentBuilder->buildContent()
      ]
    ];
  }
}

interface ContentBuilder {
  public function buildContent();
}

class FlexBubbleBuilder implements ContentBuilder {
  private $header = null;
  private $body = null;
  private $footer = null;
  private $hero = null;
  private $direction = null;
  private $styles = null;

  public function __construct() {

  }

  public static function create() {
    return new FlexBubbleBuilder();
  }

  public function setHeader($component) {
    $this->header = $component->getFormat();
    return $this;
  }
  public function setBody($component) {
    $this->body = $component->getFormat();
    return $this;
  }
  public function setFooter($component) {
    $this->footer = $component->getFormat();
    return $this;
  }
  public function setHero($component) {
    $this->hero = $component->getFormat();
    return $this;
  }
  public function setStyles(FlexComponent $components) {
    $this->styles = $components->getFormat();
    return $this;
  }
  public function buildContent() {
    if( empty($this->header) || empty($this->body) || empty($this->footer) )
      gg('Bubble 建立Content錯誤');

    $content = [
      'type' => 'bubble',
      'header' => $this->header,
      'body' => $this->body,
      'footer' => $this->footer,
    ];

    !empty($this->hero) && $content['hero'] = $this->hero;
    !empty($this->styles) && $content['styles'] = $this->styles;

    return $content;
  }
}

class FlexCarouselBuilder implements ContentBuilder {
  private $bubbleContents = [];

  public function __construct(array $bubbleContents) {
    $this->bubbleContents = $bubbleContents;
  }

  public function buildContent() {
    if( empty($this->bubbleContents) )
      return $this;
    foreach( $this->bubbleContents as $bubble ) {
      $contents[] = $bubble->buildContent();
    }
    return [
      'type' => 'carousel',
      'contents' => $contents
    ];
  }
}

class FlexComponent {
  public $properties = [];

  private $contents = [];
  private $type;
  private $layout;
  private $flex;
  private $spacing;
  private $text;
  private $url;

  private $weight;
  private $height;
  private $size;

  private $margin;
  private $backgroundColor;
  private $style;
  private $color;

  private $align;
  private $gravity;
  private $aspectRatio;
  private $aspectMode;

  private $separator;
  private $separatorColor;

  private $header;
  private $body;
  private $footer;
  private $hero;
  private $action;

  private $wrap;

  public function __construct() {

  }
  public static function create() {
    return new FlexComponent();
  }

  public function _setType($value) { if(is_string($value)) $this->type = $value; }
  public function _setLayout($value) { if(is_string($value)) $this->layout = $value; }
  public function _setFlex($value) { if(is_string($value)) $this->flex = $value; }
  public function _setSpacing($value) { if(is_string($value)) $this->spacing = $value; }
  public function _setUrl($value) { if(is_string($value)) $this->url = $value; }
  public function _setText($value) { if(is_string($value)) $this->text = $value; }
  public function _setWeight($value) { if(is_string($value)) $this->weight = $value; }
  public function _setHeight($value) { if(is_string($value)) $this->height = $value; }
  public function _setMargin($value) { if(is_string($value)) $this->margin = $value; }
  public function _setSize($value) { if(is_string($value)) $this->size = $value; }
  public function _setBackgroundColor($value) { if(is_string($value)) $this->backgroundColor = $value; }
  public function _setStyle($value) { if(is_string($value)) $this->style = $value; }
  public function _setColor($value) { if(is_string($value)) $this->color = $value; }
  public function _setAlign($value) { if(is_string($value)) $this->align = $value; }
  public function _setWrap($value) { if(is_string($value)) $this->wrap = $value; }
  public function _setGravity($value) { if(is_string($value)) $this->gravity = $value; }
  public function _setAspectRatio($value) { if(is_string($value)) $this->aspectRatio = $value; }
  public function _setAspectMode($value) { if(is_string($value)) $this->aspectMode = $value; }
  public function _setSeparator($value) { if(is_bool($value)) $this->separator = $value; }
  public function _setSeparatorColor($value) { if(is_string($value)) $this->separatorColor = $value; }

  public function _setAction($components) {
    $this->action = $components;
    return $this;
  }
  public function _setHeader($components) {
    if( empty($components) ) return $this;
    foreach($components as $pro => $value) {
      $this->header[$pro] = $value;
    }
    return $this;
  }
  public function _setBody($components) {
    if( empty($components) ) return $this;
    foreach($components as $pro => $value) {
      $this->body[$pro] = $value;
    }
    return $this;
  }
  public function _setHero($components) {
    if( empty($components) ) return $this;
    foreach($components as $pro => $value) {
      $this->hero[$pro] = $value;
    }
    return $this;
  }
  public function _setFooter($components) {
    if( empty($components) ) return $this;
    foreach($components as $pro => $value) {
      $this->footer[$pro] = $value;
    }
    return $this;
  }
  public function _setContents(array $components) {
    if( empty($components) )
      return $this;

    foreach( $components as $component ) {
      if( empty($component) )
        continue;
      foreach($component->properties as $pro)
        $content[$pro] = $component->$pro;
      $this->contents[] = $content;
    }
    return $this;
  }

  public function getFormat() {
    if( empty($this) )
      return $this;

    foreach($this->properties as $pro) {
      if( is_array( ($spro = $this->$pro) )  && isset($spro['properties']) )
        foreach($spro['properties'] as $subPro)
          $res[$pro][$subPro] = $spro[$subPro];
      else
        $res[$pro] = $this->$pro;
    }
    return $res;
  }

  public function __call($name, $args) {
    method_exists ($this, '_' . $name) || gg ('Component 錯誤的使用');
    call_user_func_array (array ($this, '_' . $name), $args);

    array_push($this->properties, lcfirst( str_replace('set', '', $name) ) );
    return $this;
  }

}

class FlexAction {
  public static function postBack($label, $text, $data) {
    return is_string($label) && is_string($text) && is_array($data) ? json_encode($data) : $data ? [ 'type' => 'postback', 'label' => $label, 'data' => $data, 'text' => $text ] : null;
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
