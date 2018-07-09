<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

Router::get ('', 'main@index');
Router::get ('intro', 'main@intro');
Router::get ('stores', 'main@stores');

Router::get ('login', 'main@login');
Router::get ('logout', 'main@logout');
Router::post ('login', 'main@ac_signin');


Router::dir ('api', function () {
  Router::get ('stores/(:id)', 'stores@index($1)', array (array ('model' => 'Store')));
  Router::post ('pvs', 'pvs@index');
});
Router::dir ('admin', function () {
  Router::get ('', 'main');

  Router::restful ('starts', 'starts', array (
    array ('model' => 'Start')));

  Router::restful ('index_header_banners', 'index_header_banners', array (
    array ('model' => 'IndexHeaderBanner')));

  Router::restful ('index_footer_banners', 'index_footer_banners', array (
    array ('model' => 'IndexFooterBanner')));

  Router::restful ('store_tags', 'store_tags', array (
    array ('model' => 'StoreTag')));

  Router::restful ('stores', 'stores', array (
    array ('model' => 'Store')));

  Router::restful ('ori_ads', 'ori_ads', array (
    array ('model' => 'OriAd')));

  Router::restful ('brands', 'brands', array (
    array ('model' => 'Brand')));

  Router::restful (array ('index_header_banner', 'pvs'), 'pv_index_header_banners', array (
    array ('model' => 'IndexHeaderBanner'), array ('model' => 'PvIndexHeaderBanner')));

  Router::restful (array ('index_footer_banner', 'pvs'), 'pv_index_footer_banners', array (
    array ('model' => 'IndexFooterBanner'), array ('model' => 'PvIndexFooterBanner')));

  Router::restful (array ('store', 'pvs'), 'pv_stores', array (
    array ('model' => 'Store'), array ('model' => 'PvStore')));

  Router::restful (array ('ori_ad', 'pvs'), 'pv_ori_ads', array (
    array ('model' => 'OriAd'), array ('model' => 'PvOriAd')));

  Router::restful (array ('brand', 'pvs'), 'pv_brands', array (
    array ('model' => 'Brand'), array ('model' => 'PvBrand')));
});
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump (Router::$routers);
// exit ();
