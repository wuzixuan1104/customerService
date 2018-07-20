<?php
namespace LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder;

class FlexMessageBuilder implements MessageBuilder {
  private $altText;
  private $contentBuilder;

  public function __construct($altText, ContentBuilder $contentBuilder) {
    $this->altText = $altText;
    $this->contentBuilder = $contentBuilder;
  }

  public function buildMessage() {
    return [
      [
        'type' => 'flex',
        'altText' => $this->altText,
        'content' => $this->contentBuilder,
      ]
    ];
  }
}

interface ContentBuilder {
  public function buildContent();
}

class BubbleBuilder implements ContentBuilder {
  private $header = null;
  private $body = null;
  private $footer = null;
  private $hero = null;
  private $direction = null;
  private $styles = null;

  public function __construct() {

  }

  public static function create() {
    return new BubbleBuilder();
  }

  public function setHeader($component) {
    $this->header = $component;
    return $this;
  }
  public function setBody($component) {
    $this->body = $component;
    return $this;
  }
  public function setFooter($component) {
    $this->footer = $component;
    return $this;
  }
  public function setHero($component) {
    $this->hero = $component;
    return $this;
  }
  public function setStyles($component) {
    $this->styles = $component;
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

class FlexComponent {
  public $properties = [];

  private $format = [];
  private $type;
  private $layout;
  private $text;
  private $url;
  private $weight;
  private $size;
  private $margin;
  private $comment;

  public function __construct() {

  }
  public static function create() {
    return new FlexComponent();
  }

  public function _setType($value) {
    if(is_string($value)) $this->type = $value;
  }
  public function _setLayout($value) {
    if(is_string($value)) $this->layout = $value;
  }
  public function _setUrl($value) {
    if(is_string($value)) $this->url = $value;
  }
  public function _setText($value) {
    if(is_string($value)) $this->text = $value;
  }
  public function _setWeight($value) {
    if(is_string($value)) $this->weight = $value;
  }
  public function _setMargin($value) {
    if(is_string($value)) $this->margin = $value;
  }
  public function _setSize($value) {
    if(is_string($value)) $this->size = $value;
  }
  public function _comment($value) {
    if(is_string($value)) $this->comment = $value;
  }
  public function setContents(array $components) {
    if( empty($components) ) return $this;

    foreach( $components as $component ) {
      if( empty($component) )
        comtinue;

      foreach($component->properties as $pro) {
        $content[$pro] = $component->$pro;
      }
      $this->format['contents'][] = $content;
    }
    return $this;
  }
  public function format() {
    foreach($this->properties as $pro) {
      $this->format[$pro] = $this->$pro;
    }
    return $this->format;
  }

  public function __call($name, $args) {
    method_exists ($this, '_' . $name) || gg ('Component 錯誤的使用');
    call_user_func_array (array ($this, '_' . $name), $args);

    array_push($this->properties, strtolower( str_replace('set', '', $name) ) );
    return $this;
  }

}
