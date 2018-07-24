<?php
Load::lib('MyLineBot.php');

class Flex {
  public function __construct() {

  }

  /*
  Flex::bubble([
    'header' => FlexBox::create([ FlexText::create('Title')->setSize('xl')->setWeight('bold') ])->setLayout('vertical'),
    'hero' => FlexImage::create('https://sitthi.me:3807/static/fifa.jpg')->setSize('full')->setAspectRatio(20:13)->setAspectMode('cover'),
    'body' =>
      FlexBox::create([
        FlexBox::create([ FlexText::create('LIVE!!')->setSize('lg')->setColor('#555555')->setWeight('bold')->setAlign('center') ])->setLayout('vertical')->setSpacing('md'),
        FlexButton::create('primary')->setAction( FlexAction::postback() ),
        FlexSeparator::create()->setMargin('lg'),
        FlexBox::create([
          FlexBox::create([
            FlexButton::create('primary')->setAction( FlexAction::postback() ),
            FlexButton::create('primary')->setAction( FlexAction::postback() ),
          ])->setLayout('horizontal')->setSpacing('sm'),
          FlexBox::create([
            FlexButton::create('primary')->setAction( FlexAction::postback() ),
            FlexButton::create('primary')->setAction( FlexAction::postback() ),
          ])->setLayout('horizontal')->setSpacing('sm'),
        ])->setLayout('vertical')->setMargin('lg')->setSpacing('sm'),
    ]),
    'footer' => FlexBox::create([ FlexButton::create('secondary')->setAction( FlexAction::postback()->setMargin('sm') ) ])
  ]);
  */

  public static function bubble() {

  }
}
class FlexComponent {
  protected $attrs = [];
  public function toArray() {
    if (!$this->attrs)
      return $this;

  }
}
class FlexBox {
  private $contents;
  private $layout;
  private $flex;
  private $margin;
  private $spacing;

  public function __construct(array $contents) {
    parent::__construct();
    $this->setContents($contents);
  }
  public static function create( $contents ) {
    return new FlexBox($contents);
  }
  public function setLayout($value) {
    if(is_string($value)) $this->layout = $value;
    return $this;
  }
  public function setContents(array $contents) {
    $this->contents = $contents;
    return $this;
  }
  public function setSpacing($value) {
    if(is_string($value)) $this->spacing = $value;
    return $this;
  }
  public function setFlex($value) {
    if(is_numeric($value)) $this->flex = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->margin = $value;
    return $this;
  }
}
class FlexButton {
  private $action;
  private $flex;
  private $margin;
  private $height;
  private $style;
  private $color;
  private $gravity;

  public function __construct($style) {
    $this->setStyle($style);
  }
  public static function create($style) {
    return new FlexButton($style);
  }
  public function setAction() {

  }
  public function setFlex($value) {
    if(is_string($value)) $this->flex = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->margin = $value;
    return $this;
  }
  public function setHeight($value) {
    if(is_string($value)) $this->height = $value;
    return $this;
  }
  public function setStyle($value) {
    if(is_string($value)) $this->style = $value;
    return $this;
  }
  public function setColor($value) {
    if(is_string($value)) $this->color = $value;
    return $this;
  }
  public function setGravity($value) {
    if(is_string($value)) $this->gravity = $value;
    return $this;
  }
}
class FlexIcon {
  private $url;
  private $margin;
  private $size;
  private $aspectRatio;

  public function __construct($url) {
    $this->setUrl($url);
  }
  public static function create($url) {
    return new FlexIcon($url);
  }
  public function setUrl($value) {
    if(is_string($value)) $this->url = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->margin = $value;
    return $this;
  }
  public function setSize($value) {
    if(is_string($value)) $this->size = $value;
    return $this;
  }
  public function setAspectRatio($value) {
    if(is_string($value)) $this->aspectRatio = $value;
    return $this;
  }
}
class FlexImage {
  private $url;
  private $flex;
  private $margin;
  private $align;
  private $gravity;
  private $size;
  private $aspectRatio;
  private $aspectMode;
  private $backgroundColor;

  public function __construct($url) {
    $this->setUrl($url);
  }
  public static function create($url) {
    return new FlexImage($url);
  }
  public function setUrl($value) {
    if(is_string($value)) $this->url = $value;
    return $this;
  }
  public function setFlex($value) {
    if(is_numeric($value)) $this->flex = $value;
    return $this;
  }
  public function setMargin($value) {
    if(is_string($value)) $this->margin = $value;
    return $this;
  }
  public function setAlign($value) {
    if(is_string($value)) $this->align = $value;
    return $this;
  }
  public function setGravity($value) {
    if(is_string($value)) $this->gravity = $value;
    return $this;
  }
  public function setSize($value) {
    if(is_string($value)) $this->size = $value;
    return $this;
  }
  public function setAspectRatio($value) {
    if(is_string($value)) $this->aspectRatio = $value;
    return $this;
  }
  public function setAspectMode($value) {
    if(is_string($value)) $this->aspectMode = $value;
    return $this;
  }
  public function setBackgroundColor($value) {
    if(is_string($value)) $this->backgroundColor = $value;
    return $this;
  }
  public function setAction() {

  }
}
class FlexSeparator {
  private $margin;
  private $color;

  public function __construct() {

  }
  public static function create() {
    return new FlexSeparator();
  }
  public function setMargin() {

  }
  public function setColor() {

  }
}
class FlexSpacer {
  public function __construct($size) {
    $this->setSize($size);
  }
  public static function create($size) {
    return new FlexSpacer($size);
  }
  public function setSize($value) {
    if(is_string($value)) $this->size = $value;
    return $this;
  }
}
class FlexText extends FlexComponent {

  public function __construct($text) {
    parent::__construct();
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

  }

}
