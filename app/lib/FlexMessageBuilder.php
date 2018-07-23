<?php
namespace LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder;

class FlexMessageBuilder implements MessageBuilder {
  private $type;
  private $altText;
  private $contentBuilder;

  public function __construct($altText, array $contentBuilder) {
    $this->type = 'flex';
    $this->altText = $altText;
    $this->contentBuilder = $contentBuilder;
  }

  public function buildMessage() {
    return [
      [
        'type' => $this->type,
        'altText' => $this->altText,
        'contents' => $this->contentBuilder,
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

class FlexComponent {
  public $properties = [];

  private $contents = [];
  private $type;
  private $layout;
  private $text;
  private $url;
  private $weight;
  private $size;
  private $margin;
  private $backgroundColor;
  private $separator;
  private $separatorColor;

  private $header;
  private $body;
  private $footer;
  private $hero;
  private $comment;

  public function __construct() {

  }
  public static function create() {
    return new FlexComponent();
  }

  public function _setType($value) { if(is_string($value)) $this->type = $value; }
  public function _setLayout($value) { if(is_string($value)) $this->layout = $value; }
  public function _setUrl($value) { if(is_string($value)) $this->url = $value; }
  public function _setText($value) { if(is_string($value)) $this->text = $value; }
  public function _setWeight($value) { if(is_string($value)) $this->weight = $value; }
  public function _setMargin($value) { if(is_string($value)) $this->margin = $value; }
  public function _setSize($value) { if(is_string($value)) $this->size = $value; }
  public function _setBackgroundColor($value) { if(is_string($value)) $this->backgroundColor = $value; }
  public function _setSeparator($value) { if(is_bool($value)) $this->separator = $value; }
  public function _setSeparatorColor($value) { if(is_string($value)) $this->separatorColor = $value; }

  public function _setHeader($components) {
    if( empty($components) ) return $this;
    foreach($components as $pro => $value) {
      $this->header[$pro] = $value;
    }
    return $this;
  }
  public function _setComment($components) {
    if( empty($components) ) return $this;
    foreach($components as $pro => $value) {
      $this->comment[$pro] = $value;
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
    if( empty($components) ) return $this;

    foreach( $components as $component ) {
      if( empty($component) )
        continue;

      foreach($component->properties as $pro) {
        $content[$pro] = $component->$pro;
      }
      $this->contents = $content;
      // $this->format['contents'][] = $content;
    }
    return $this;
  }

  public function getFormat() {
    if( empty($this) ) return $this;
    foreach($this->properties as $pro) {
      if( is_array( ($spro = $this->$pro) )  && isset($spro['properties']) ) {
        foreach($spro['properties'] as $subPro) {
          $res[$pro][$subPro] = $spro[$subPro];
        }
      } else
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
