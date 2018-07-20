<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */


use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\BubbleBuilder;
use LINE\LINEBot\MessageBuilder\FlexComponent;

class main extends Controller {

  public function replyMessage($replyToken, LINE\LINEBot\MessageBuilder $messageBuilder)
  {
      return $this->httpClient->post($this->endpointBase . '/v2/bot/message/reply', [
          'replyToken' => $replyToken,
          'messages' => $messageBuilder->buildMessage(),
      ]);
  }

  public function index () {
    Load::lib('FlexMessageBuilder.php');

    $res = new FlexMessageBuilder('altText',
            BubbleBuilder::create()
            ->setHeader( FlexComponent::create()->setType('box')->setLayout('vertical')->setContents([
              FlexComponent::create()->setType('text')->setText('Header text')])->format()
            )->setBody( FlexComponent::create()->setType('box')->setLayout('vertical')->setContents([
              FlexComponent::create()->setType('text')->setText('Body text')])->format()
            )->setFooter( FlexComponent::create()->setType('box')->setLayout('vertical')->setContents([
              FlexComponent::create()->setType('text')->setText('Footer text')])->format()
            )->setHero( FlexComponent::create()->setType('image')->setUrl('https://example.com/flex/images/image.jpg')->format()
            ));
    // print_r($res);
    // die;
    $res = $this->replyMessage('123', $res);

    print_r($res);
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
