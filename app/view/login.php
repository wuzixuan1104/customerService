<!DOCTYPE html>
<html lang="tw">
  <head>
    <meta http-equiv="Content-Language" content="zh-tw" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui" />

    <title>登入後台系統</title>

    <link href="<?php echo asset ('/assets/css/icon-login.css');?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo asset ('/assets/css/login.css');?>" rel="stylesheet" type="text/css" />

    <script src="<?php echo asset ('/assets/js/res/jquery-1.10.2.min.js');?>" language="javascript" type="text/javascript" ></script>
    <script src="<?php echo asset ('/assets/js/login.js');?>" language="javascript" type="text/javascript" ></script>

  </head>
  <body lang="zh-tw">
    
    <main id='main'>
      <h1>
        <span>登入後台系統</span>
      </h1>

      <form class='login' action='<?php echo URL::base ('login');?>' method='post'>
      
        <div>
          <div class='acc-psw'>
            <span<?php echo $flash['type'] ? ' class="' . $flash['type'] . '"' : '';?>><?php echo $flash['msg'];?></span>
            <label>
              <b>帳號</b>
              <div class='icon-user'><input type='text' name='account' placeholder='請輸入您的帳號'></div>
            </label>

            <label>
              <b>密碼</b>
              <div class='icon-key'><input type='password' name='password' placeholder='請輸入您的密碼'></div>
            </label>

            <button type='submit'>登入</button>
          </div>
        </div>

      </form>
      <span>© 2014 - <?php echo date ('Y');?> www.ioa.tw | 後台版型設計 by <a href='https://www.ioa.tw/' target='_blank'>OAWU</a></span>
    </main>

  </body>
</html>
