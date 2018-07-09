<!DOCTYPE html>
<html lang="tw">
  <head>
    <meta http-equiv="Content-Language" content="zh-tw" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui" />

    <title>後台系統</title>

    <?php echo $asset->renderCSS ();?>
    <?php echo $asset->renderJS ();?>

  </head>
  <body lang="zh-tw">
    
    <main id='main'>
      <header id='main-header'>
        <a id='hamburger' class='icon-01'></a>
        <nav><b><?php echo isset ($title) && $title ? $title : '';?></b></nav>
        <a href='<?php echo URL::base ('logout');?>' class='icon-02'></a>
      </header>

      <div class='flash <?php echo $flash['type'];?>'><?php echo $flash['msg'];?></div>

      <div id='container'>
  <?php echo isset ($content) ? $content : ''; ?>
      </div>

    </main>

    <div id='menu'>
      <header id='menu-header'>
        <a href='<?php echo URL::base ();?>' class='icon-21'></a>
        <span>後台管理系統</span>
      </header>

      <div id='menu-user'>
        <figure class='_ic'>
          <img src="/assets/img/user.png">
        </figure>

        <div>
          <span>Hi, 您好!</span>
          <b>管理員</b>
        </div>
      </div>

      <div id='menu-main'>
        <div>
          <span class='icon-14'>首頁區</span>
          <div>
            <a href="<?php echo $url = RestfulUrl::url ('admin/starts@index');?>" class='icon-21<?php echo isset ($current_url) && $url === $current_url ? ' active' : '';?>'>開始使用按鈕</a>
            <a href="<?php echo $url = RestfulUrl::url ('admin/index_header_banners@index');?>" class='icon-19<?php echo isset ($current_url) && $url === $current_url ? ' active' : '';?>'>上方廣告輪播</a>
            <a href="<?php echo $url = RestfulUrl::url ('admin/index_footer_banners@index');?>" class='icon-19<?php echo isset ($current_url) && $url === $current_url ? ' active' : '';?>'>下方廣告輪播</a>
          </div>
        </div>
        <div>
          <span class='icon-14'>商家管理</span>
          <div>
            <a href="<?php echo $url = RestfulUrl::url ('admin/store_tags@index');?>" class='icon-42<?php echo isset ($current_url) && $url === $current_url ? ' active' : '';?>'>商家分類</a>
            <a href="<?php echo $url = RestfulUrl::url ('admin/stores@index');?>" class='icon-21<?php echo isset ($current_url) && $url === $current_url ? ' active' : '';?>'>商家列表</a>
          </div>
        </div>
        <div>
          <span class='icon-14'>美食情報</span>
          <div>
            <a href="<?php echo $url = RestfulUrl::url ('admin/ori_ads@index');?>" class='icon-21<?php echo isset ($current_url) && $url === $current_url ? ' active' : '';?>'>原生廣告</a>
            <a href="<?php echo $url = RestfulUrl::url ('admin/brands@index');?>" class='icon-21<?php echo isset ($current_url) && $url === $current_url ? ' active' : '';?>'>品牌輪播</a>
          </div>
        </div>

      </div>
    </div>

    <footer id='footer'><span>後台版型設計 by </span><a href='https://www.ioa.tw/' target='_blank'>OA Wu</a></footer>

  </body>
</html>
