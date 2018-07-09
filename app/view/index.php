<!DOCTYPE html>
<html lang="tw">
  <head>
    <meta http-equiv="Content-Language" content="zh-tw" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui" />

    <title>Ximen Free Wifi 西門智慧商圈</title>

    <?php echo $asset->renderCSS ();?>
    <?php echo $asset->renderJS ();?>

  </head>
  <body lang="zh-tw">
    <div id='cover'></div>
    <div id='top' data-i='1'>
<?php foreach ($hBanners as $banner) { ?>
        <a class='img' href='<?php echo $banner->link;?>'>
          <img src="<?php echo $banner->pic->url ('min');?>">
        </a>
<?php }?>
    </div>
    
    <div id='mid'>
      <a class='img' href='<?php echo Url::base ('stores');?>'>
        <img src="/assets/img/store.jpg" >
      </a>
    </div>

    <div id='bot' data-i='1'>
<?php foreach ($fBanners as $banner) { ?>
        <a class='img' href='<?php echo $banner->link;?>'>
          <img src="<?php echo $banner->pic->url ('min');?>">
        </a>
<?php }?>
    </div>

    <form id='start' action="http://125.227.37.68/auth/index.html/u" method="post">
      <input type="hidden" name="tag" value=""/>
      <input type="hidden" name="gid" value=""/>
      <input type="hidden" name="g_address" value=""/>
      <input type="hidden" name="email" value="nodata@demo.com"/>
      <input type="hidden" name="cmd" value="authenticate"/>

      <div id="start-img" onclick="window.open ('<?php echo Url::base ('intro');?>','_blank');$('#start').submit ();">
        <img src="<?php echo $start->pic->url ('wh710_400');?>">
      </div>
    </form>
  </body>
</html>
