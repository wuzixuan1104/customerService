<!DOCTYPE html>
<html lang="tw">
  <head>
    <meta http-equiv="Content-Language" content="zh-tw" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui" />

    <title>Trello</title>

    <?php echo $asset->renderCSS ();?>
    <?php echo $asset->renderJS ();?>

    <script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
    <script src="https://api.trello.com/1/client.js?key=4bf05439215ed92eafbb4e26d4064f2e"></script>

  </head>
  <body lang="zh-tw">
    <script>
    $(function() {
      $.post("https://api.trello.com/1/tokens/201fb7dc9a43e1bebc0e8fedec8e16d45a7c7c0922f828da539324f60640c5eb/webhooks/?key=4bf05439215ed92eafbb4e26d4064f2e", {
        description: "My first webhook",
        callbackURL: "http://qa.kerker.tw/api/trelloCallback",
        idModel: "5b486c17c801b2d657ed9f99",
      });
      console.log('test');
    });
    </script>

    Hello!!
  </body>
</html>
