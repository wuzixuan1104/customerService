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
    var authenticationSuccess = function() {
      console.log('Successful authentication');
    };

    var authenticationFailure = function() {
      console.log('Failed authentication');
    };
    var url="https://trello.com/c/QyeGYHdi/1-card-1";


    window.Trello.authorize({
      type: 'popup',
      name: 'Getting Started Application',
      scope: {
        read: 'true',
        write: 'true' },
      expiration: 'never',
      success: authenticationSuccess,
      error: authenticationFailure
    });

    var myList = "5b42cf2739596c211c1e176b";

    var creationSuccess = function (data) {
      console.log('我成功惹！');
      console.log(JSON.stringify(data, null, 2));
    };

    var newCard = {
      name: '你是笨蛋笨蛋',
      desc: '笨蛋是什麼～～～',
      idList: myList,
      pos: 'top'
    };

    window.Trello.post('/cards/', newCard, creationSuccess);
    



    </script>

    Hello!!
  </body>
</html>
