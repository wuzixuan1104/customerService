<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */



class main extends Controller {

  public function index () {
    Load::lib('MyLineBot.php');
    Load::lib('FlexFormat.php');

    $a = FlexCarousel::create([
    FlexBubble::create([
      'header' => FlexBox::create([ FlexText::create('Title')->setSize('xl')->setWeight('bold') ])->setLayout('vertical'),
      'hero' => FlexImage::create('https://sitthi.me:3807/static/fifa.jpg')->setSize('full')->setAspectRatio('20:13')->setAspectMode('cover'),
      'body' =>
        FlexBox::create([
          FlexBox::create([ FlexText::create('LIVE!!')->setSize('lg')->setColor('#555555')->setWeight('bold')->setAlign('center') ])->setLayout('vertical')->setSpacing('md'),
          FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
          FlexSeparator::create()->setMargin('lg'),
          FlexBox::create([
            FlexBox::create([
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
            ])->setLayout('horizontal')->setSpacing('sm'),
            FlexBox::create([
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
            ])->setLayout('horizontal')->setSpacing('sm'),
          ])->setLayout('vertical')->setMargin('lg')->setSpacing('sm'),
        ])->setLayout('vertical'),
      'footer' => FlexBox::create([ FlexButton::create('secondary')->setAction( FlexAction::postback('a', '123', 'e') )->setMargin('sm') ])->setLayout('vertical')

    ]),

    FlexBubble::create([
      'header' => FlexBox::create([ FlexText::create('Title')->setSize('xl')->setWeight('bold') ])->setLayout('vertical'),
      'hero' => FlexImage::create('https://sitthi.me:3807/static/fifa.jpg')->setSize('full')->setAspectRatio('20:13')->setAspectMode('cover'),
      'body' =>
        FlexBox::create([
          FlexBox::create([ FlexText::create('LIVE!!')->setSize('lg')->setColor('#555555')->setWeight('bold')->setAlign('center') ])->setLayout('vertical')->setSpacing('md'),
          FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
          FlexSeparator::create()->setMargin('lg'),
          FlexBox::create([
            FlexBox::create([
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
            ])->setLayout('horizontal')->setSpacing('sm'),
            FlexBox::create([
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
              FlexButton::create('primary')->setAction( FlexAction::postback('a', '123', 'e') ),
            ])->setLayout('horizontal')->setSpacing('sm'),
          ])->setLayout('vertical')->setMargin('lg')->setSpacing('sm'),
        ])->setLayout('vertical'),
      'footer' => FlexBox::create([ FlexButton::create('secondary')->setAction( FlexAction::postback('a', '123', 'e') )->setMargin('sm') ])->setLayout('vertical')

    ]),
  ]);
    print_R($a);
    die;

    $asset = Asset::create (2)
                  // ->addCSS ('/assets/css/icon-site.css')
                  // ->addCSS ('/assets/css/site/layout.css')

                  // ->addJS ('/assets/js/res/jquery-1.10.2.min.js')
                  // ->addJS ('/assets/js/res/imgLiquid-min.js')
                  // ->addJS ('/assets/js/site/layout.js')
             ;

    return View::create ('index.php')
               ->with ('asset', $asset);
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
