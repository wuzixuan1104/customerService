<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

abstract class AdminController extends Controller {
  public $layout, $view, $asset, $form;

  public function __construct () {
    parent::__construct ();

    if (!User::current ())
      return refresh (URL::base ('login'));

    $flash = Session::getFlashData ('flash');

    $this->asset = Asset::create (2)
                        ->addCSS ('/assets/css/jqui-datepick-20180116.css')
                        ->addCSS ('/assets/css/icon-admin.css')
                        ->addCSS ('/assets/css/admin/layout.css')

                        ->addJS ('/assets/js/res/jquery-1.10.2.min.js')
                        
                        ->addJS ('/assets/js/res/jquery_ui_v1.12.0.js')
                        ->addJS ('/assets/js/res/jquery_ujs.js')
                        ->addJS ('/assets/js/res/imgLiquid-min.js')
                        ->addJS ('/assets/js/res/timeago.js')
                        ->addJS ('/assets/js/res/jqui-datepick-20180116.js')
                        ->addJS ('/assets/js/res/oaips-20180115.js')
                        ->addJS ('/assets/js/res/autosize-3.0.8.js')
                        ->addJS ('/assets/js/res/OAdropUploadImg-20180115.js')
                        ->addJS ('/assets/js/res/ckeditor_d2015_05_18/ckeditor.js')
                        ->addJS ('/assets/js/res/ckeditor_d2015_05_18/adapters/jquery.js')
                        ->addJS ('/assets/js/res/ckeditor_d2015_05_18/plugins/tabletools/tableresize.js')
                        ->addJS ('/assets/js/res/ckeditor_d2015_05_18/plugins/dropler/dropler.js')
                        
                        ->addJS ('/assets/js/admin/layout.js');


    $this->layout = View::create ('admin.php')
                        ->with ('flash', $flash)
                        ->with ('asset', $this->asset);
// $flash['params'] = array (
//         'sources' => array (
//             array ('title' => 'ccccc', 'user' => 1),
//             array ('title' => 'aaaaaaa', 'user' => array ())
//             )
//     );
// $flash['params']['name'] = 'asd';
// $flash['params']['user_id'] = '';
// $flash['params']['tag_ids[]'] = array ();
// $flash['params']['action'] = Article::ACTION_SELF;
// $flash['params']['status'] = Article::STATUS_ON;

    get_flash_params ($flash['params']);

    $this->view = View::create ()
                      ->appendTo ($this->layout, 'content')
                      ->with ('asset', $this->asset);

    Pagination::$firstClass = 'icon-30';
    Pagination::$prevClass = 'icon-05';
    Pagination::$activeClass = 'active';
    Pagination::$nextClass = 'icon-06';
    Pagination::$lastClass = 'icon-31';

    Pagination::$firstText = '';
    Pagination::$lastText = '';
    Pagination::$prevText = '';
    Pagination::$nextText = '';
  }
}
