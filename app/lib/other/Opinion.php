<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Load::lib ('MyLineBot.php');

class Opinion {
  public static function send() {
    ($args = func_get_args()) && ($source = $args[1]) && ($process = json_decode($source->process, true));

    if(!Opinion::create(['source_id' => $source->id, 'card_id' => 0, 'servicer_id' => 0, 'score' => '', 'content' => $process['content']]))
      return MyLineBotMsg::create()->text('系統發生錯誤');

    return MyLineBotMsg::create()->text('已順利送出您的意見回饋');
  }
}
