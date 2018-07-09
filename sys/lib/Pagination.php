<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Pagination {
  public static $firstClass = 'f';
  public static $prevClass = 'p';
  public static $activeClass = 'a';
  public static $nextClass = 'n';
  public static $lastClass = 'l';
  public static $pageClass = '';

  public static $firstText = '第一頁';
  public static $lastText = '最後頁';
  public static $prevText = '上一頁';
  public static $nextText = '下一頁';


  public static $limitD4 = 10;
  public static $offsetKey = 'offset';
  public static $limitKey = 'limit';
  
  public static function info ($total, $limit = null, $max = 3) {
    $gets = Input::get ();

    $offsetKey = Pagination::$offsetKey;
    $limitKey = Pagination::$limitKey;

    !is_numeric ($limit) || $gets[$limitKey] = $limit;
    isset ($gets[$limitKey]) || $gets[$limitKey] = Pagination::$limitD4;
    isset ($gets[$offsetKey]) || $gets[$offsetKey] = 1;

    if (!($total && ($cnt = (int)ceil ($total / $gets[$limitKey])) > 1))
      return array (
          'offset' => ($gets[$offsetKey] - 1) * $gets[$limitKey],
          'limit' => $gets[$limitKey],
          'links' => array ()
        );

    $gets[$offsetKey] < 1 && $gets[$offsetKey] = 1;
    $gets[$offsetKey] > $cnt && $gets[$offsetKey] = $cnt;

    $total < ($max * 2 + 1) && $max = 0;

    $start = $max ? $gets[$offsetKey] - $max : 1;
    $start > 0 || ($max += -$start + 1) && $start = 1;

    $end = $max ? $gets[$offsetKey] + $max : $cnt;
    $end < $cnt || ($start += $cnt - $end) && $end = $cnt;
    $start > 0 || $start = 1;

    $links = array ();
    
    if ($start != 1)
      array_push ($links, array ('text' => Pagination::$firstText, 'offset' => 1, 'classes' => array (Pagination::$firstClass)));

    if ($start != $gets[$offsetKey])
      array_push ($links, array ('text' => Pagination::$prevText, 'offset' => $gets[$offsetKey] - 1, 'classes' => array (Pagination::$prevClass)));

    for ($i = $start; $i <= $end; $i++)
      array_push ($links, array ('text' => $i, 'offset' => $i, 'classes' => array (Pagination::$pageClass, $i == $gets[$offsetKey] ? Pagination::$activeClass : '')));

    if ($end != $gets[$offsetKey])
      array_push ($links, array ('text' => Pagination::$nextText, 'offset' => $gets[$offsetKey] + 1, 'classes' => array (Pagination::$nextClass)));

    if ($end != $cnt)
      array_push ($links, array ('text' => Pagination::$lastText, 'offset' => $cnt, 'classes' => array (Pagination::$lastClass)));

    return array (
        'offset' => ($gets[$offsetKey] - 1) * $gets[$limitKey],
        'limit' => $gets[$limitKey],
        'links' => array_map (function ($link) use ($gets, $offsetKey) {
            $gets[$offsetKey] = $link['offset'];
            return '<a href="' . ('?' . http_build_query ($gets)) . '"' . (($t = implode (' ', array_filter ($link['classes']))) ? ' class="' . $t . '"' : '') . '>' . $link['text'] . '</a>';
          }, $links)
      );
  }
}
