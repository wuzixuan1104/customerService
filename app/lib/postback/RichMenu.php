<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Load::lib ('MyLineBot.php');

class Qa {

  public static function create() {
    if(!$source = func_get_args()[1]) 
      return false;

    if(!$cards = Card::find('all', array('where' => array('source_id = ? AND created_at >= date_sub(now(), interval 1 month)', $source->id))))
      return MyLineBotMsg::create()->text('尚無進行中的問題');

    array_map(function($card) use (&$format) {
      !$card->list ? null : $format[$card->list->name][] = $card;
    }, $cards);
    
    $flexes = $bubbles = [];
    $cnt = 0;
    foreach($format as $formatTypes => $contents) {
      if(count($bubbles) > 4)
          break;

      $flexes[] = FlexText::create($formatTypes)->setColor('#12776e')->setWeight('bold');
      $flexes[] = FlexSeparator::create();

      $cnt++;

      foreach($contents as $content) {
        if(count($bubbles) > 4)
          break;

        $flexes[] = FlexBox::create([
                      FlexBox::create([FlexBox::create([
                        FlexText::create('Q.' .  $content->name),
                        FlexBox::create([FlexText::create(Card::STATUS_DEAL == $content->status ? '處理中...' : '待處理')->setSize('xxs')->setAlign('start')->setColor(Card::STATUS_DEAL == $content->status ? '#f37370' : '#bbbbbb'), FlexText::create($content->created_at->format('Y-m-d'))->setSize('xxs')->setAlign('end')->setColor('#bbbbbb')])->setLayout('horizontal')->setMargin('lg')
                      ])->setLayout('vertical')])->setLayout('vertical')->setFlex(7),
                      FlexSeparator::create(),
                      FlexButton::create('primary')->setColor('#f37370')->setFlex(3)->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('切換', '您已按了切換', json_encode(array('lib' => 'postback/RichMenu', 'class' => 'Qa', 'method' => 'checkoutCard', 'param' => array('card_id' => $content->id, 'title' => 'Q.' .  $content->name) )  ) ))
                    ])->setLayout('horizontal')->setSpacing('md');
        $flexes[] = FlexSeparator::create();

        if((++$cnt) >= 5) {
          $bubbles[] = FlexBubble::create([
                          'header' => FlexBox::create([ FlexText::create('問題列表 - 正在進行中')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
                          'body' => FlexBox::create($flexes)->setLayout('vertical')->setSpacing('md')->setMargin('sm'),
                          'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
                        ]);
          $flexes = [];
          $cnt = 0;
        }
      }
    }

    if($flexes) {
      $bubbles[] = FlexBubble::create([
                      'header' => FlexBox::create([ FlexText::create('問題列表 - 正在進行中')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2') ])->setSpacing('xs')->setLayout('horizontal'),
                      'body' => FlexBox::create($flexes)->setLayout('vertical')->setSpacing('md')->setMargin('sm'),
                      'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))]);
    }

    return MyLineBotMsg::create()->flex('問題列表 - 正在進行中', FlexCarousel::create($bubbles)); 
  }

  public static function checkoutCard() {
    $data = func_get_args();
    if(!(($cardId = $data[0]['card_id']) && ($title = $data[0]['title'])) ) 
      return false;

    if(!Card::find('one', ['where' => ['id = ?', $cardId]]))
      return false;

    //切換卡片流程

    return MyLineBotMsg::create()->flex('已切換問題', FlexBubble::create([
            'header' => FlexBox::create([FlexText::create('已切換問題')->setWeight('bold')->setSize('lg')->setColor('#e8f6f2')])->setSpacing('xs')->setLayout('horizontal'),
            'body' => FlexBox::create([
              FlexText::create($title)->setWeight('bold')->setColor('#307671'),
              FlexSeparator::create()->setMargin('xxl'),
              FlexBox::create([
                FlexButton::create('primary')->setColor('#fbd785')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('檢視先前的對話紀錄', '查看對話紀錄', json_encode(['lib' => 'postback/RichMenu', 'class' => 'Qa', 'method' => 'dialogRecord', 'param' => ['card_id' => $cardId]]))),
                FlexButton::create('primary')->setColor('#f97172')->setHeight('sm')->setGravity('center')->setAction(FlexAction::postback('回覆訊息後按此送出', '送出訊息', json_encode(['lib' => 'postback/RichMenu', 'class' => 'Qa', 'method' => 'dialogRecord', 'param' => ['card_id' => $cardId]])))
              ])->setLayout('vertical')->setMargin('xxl')->setSpacing('sm')
            ])->setLayout('vertical'),
            'styles' => FlexStyles::create()->setHeader(FlexBlock::create()->setBackgroundColor('#12776e'))
          ]));
  }

  public static function dialogRecord() {

  }
}

class Menu {
  public static function create() {

  }
}

class Contact {
  public static function create() {

  }
}

