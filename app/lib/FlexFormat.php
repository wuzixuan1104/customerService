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

}

class FlexBox extends FlexComponent{
  public $contents;
  public $layout;
  public $flex;
  public $margin;
  protected $spacing;

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
  public function __construct() {

  }
  public function setAction() {

  }
  public function setFlex() {

  }
  public function setMargin() {

  }
  public function setHeight() {

  }
  public function setStyle() {

  }
  public function setColor() {

  }
  public function setGravity() {

  }
}
class FlexIcon {
  public function __construct() {

  }
  public function setUrl() {

  }
  public function setMargin() {

  }
  public function setSize() {

  }
  public function setAspectRatio() {

  }
}
class FlexImage {
  public function __construct() {

  }
  public function setUrl() {

  }
  public function setFlex() {

  }
  public function setMargin() {

  }
  public function setAlign() {

  }
  public function setGravity() {

  }
  public function setSize() {

  }
  public function setAspectRatio() {

  }
  public function setAspectMode() {

  }
  public function setBackgroundColor() {

  }
  public function setAction() {

  }
}
class FlexSeparator {
  public function __construct() {

  }
  public function setMargin() {

  }
  public function setColor() {

  }
}
class FlexSpacer {
  public function __construct() {

  }
  public function setSize() {}
}
class FlexText {

  public function __construct() {

  }
  public function setText() {

  }
}
