<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Router::get ('', 'main@index');


Router::dir ('api', function () {
  Router::post('line', 'Line@index');
  Router::post('trelloCallback', 'Trello@callback');
  Router::head('trelloCallback', 'Trello@callback');
});

Router::dir ('admin', function () {
  Router::get ('', 'main');

});
