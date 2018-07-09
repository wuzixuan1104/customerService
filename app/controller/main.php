<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class main extends Controller {

  public function stores () {
    $f = Input::get ('f');
    $t = Input::get ('t');

    $asset = Asset::create (2)
                  ->addCSS ('/assets/css/icon-site.css')
                  ->addCSS ('/assets/css/site/layout.css')

                  ->addJS ('/assets/js/res/jquery-1.10.2.min.js')
                  ->addJS ('/assets/js/res/imgLiquid-min.js')
                  ->addJS ('/assets/js/site/layout.js');

    if( !empty($f) ) {
      $where = Where::create( 'name LIKE ?', '%'.$f.'%' );
      $where->or( 'content LIKE ?', '%'.$f.'%' );
      $where->and( 'status = ?', Store::STATUS_ON );
    } else {
      $where = Where::create( 'status = ?', Store::STATUS_ON  );
    }

    if( !empty($t) ) {
      $where->and( 'store_tag_id = ?', $t );
    }

    $stores = Store::find( 'all', array ('order' => 'sort DESC', 'where' => $where ) );

    $oriAds = OriAd::find('all', array('order' => 'sort DESC', 'where' => array('status = ?', Store::STATUS_ON ) ) );
    $boxes = call_user_func_array( 'array_merge', array_map( function( $stores, $oriAd ) {
      $oriAd && $stores[] = $oriAd;
      return $stores;
    }, array_chunk($stores, 5),  $oriAds ) );

    $brands = Brand::find('all', array('order' => 'sort DESC', 'where' => array('status = ?', Store::STATUS_ON ) ) );
    $storeTags = StoreTag::find('all');

    return View::create ('stores.php')
               ->with ('asset', $asset)
               ->with ('f', $f)
               ->with ('t', $t)
               ->with ('boxes', $boxes)
               ->with ('brands', $brands)
               ->with ('storeTags', $storeTags);
  }

  public function intro () {
    $asset = Asset::create (2)
                  ->addCSS ('/assets/css/icon-site.css')
                  ->addCSS ('/assets/css/site/layout.css')

                  ->addJS ('/assets/js/res/jquery-1.10.2.min.js')
                  ->addJS ('/assets/js/res/imgLiquid-min.js')
                  ->addJS ('/assets/js/site/layout.js');

    $hBanners = IndexHeaderBanner::find ('all', array ('order' => 'sort DESC', 'where' => array ('status = ?', IndexHeaderBanner::STATUS_ON)));
    $fBanners = IndexFooterBanner::find ('all', array ('order' => 'sort DESC', 'where' => array ('status = ?', IndexFooterBanner::STATUS_ON)));

    return View::create ('intro.php')
               ->with ('asset', $asset)
               ->with ('hBanners', $hBanners)
               ->with ('fBanners', $fBanners);
  }

  public function index () {
    $asset = Asset::create (2)
                  ->addCSS ('/assets/css/icon-site.css')
                  ->addCSS ('/assets/css/site/layout.css')

                  ->addJS ('/assets/js/res/jquery-1.10.2.min.js')
                  ->addJS ('/assets/js/res/imgLiquid-min.js')
                  ->addJS ('/assets/js/site/layout.js')
             ;

    $hBanners = IndexHeaderBanner::find ('all', array ('order' => 'sort DESC', 'where' => array ('status = ?', IndexHeaderBanner::STATUS_ON)));
    $start = Start::find ('one', array ('order' => 'id DESC', 'where' => array ()));
    $fBanners = IndexFooterBanner::find ('all', array ('order' => 'sort DESC', 'where' => array ('status = ?', IndexFooterBanner::STATUS_ON)));

    return View::create ('index.php')
               ->with ('asset', $asset)
               ->with ('hBanners', $hBanners)
               ->with ('start', $start)
               ->with ('fBanners', $fBanners);
  }

  public function logout () {
    Session::unsetData ('token');
    return refresh (URL::base ('login'), 'flash', array ('type' => 'success', 'msg' => '登出成功！', 'params' => array ()));
  }

  public function login () {
    if (@User::current ()->id)
      return refresh (URL::base ('admin'));

    $from = Input::get ('f');
    $flash = Session::getFlashData ('flash');

    return View::create ('login.php')
               ->with ('from', $from)
               ->with ('flash', $flash)
               ->with ('params', $flash['params'])
               ->output ();
  }

  public function ac_signin () {
    $validation = function (&$posts, &$user) {
      Validation::need ($posts, 'account', '帳號')->isStringOrNumber ()->doTrim ()->length (1, 255);
      Validation::need ($posts, 'password', '密碼')->isStringOrNumber ()->doTrim ()->length (1, 255);

      if (!$user = User::find ('one', array ('select' => 'id, account, password, token', 'where' => array ('account = ?', $posts['account']))))
        Validation::error ('此帳號不存在！');

      if (!password_verify ($posts['password'], $user->password))
        Validation::error ('密碼錯誤！');
    };

    $transaction = function ($user) {
      $user->token || $user->token = md5 (($user->id ? $user->id . '_' : '') . uniqid (rand () . '_'));
      return $user->save ();
    };

    $posts = Input::post ();

    if ($error = Validation::form ($validation, $posts, $user))
      return refresh (URL::base ('login'), 'flash', array ('type' => 'failure', 'msg' => $error, 'params' => $posts));

    if ($error = User::getTransactionError ($transaction, $user))
      return refresh (URL::base ('login'), 'flash', array ('type' => 'failure', 'msg' => $error, 'params' => $posts));

    Session::setData ('token', $user->token);
    return refresh (URL::base ('admin'), 'flash', array ('type' => 'success', 'msg' => '登入成功！', 'params' => array ()));
  }
}
