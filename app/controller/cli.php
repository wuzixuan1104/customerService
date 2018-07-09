<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class cli extends Controller {

  public function index () {
    Load::lib ('phpQuery.php');

    $url = 'http://occupy.sungchin.com/ximen/comebuy/';

    if (!($get_html_str = str_replace ('&amp;', '&', urldecode (file_get_contents ($url)))))
      exit ('取不到原始碼！');

    // echo $get_html_str;
    $query = phpQuery::newDocument ($get_html_str);
    $title = pq (".info-list", $query);

    // // $title->text ();

    // for ($i=0; $i < $title->length (); $i++) { 
    //   $title->eq ($i)->text ()
    // }

    var_dump ($title->length ());
    exit ();

  }
}
