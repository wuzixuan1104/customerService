/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2018, OAF2E
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

$(function () {
  var box = {
    $el: null,
    $el2: null,
    init: function () {
      $('body').append (
        box.$el = $('<div />').attr ('id', 'box')).append (
        $('<div />').attr ('class', '_c')).append (
        box.$el2 = $('<div />').attr ('id', 'img')).append (
        $('<div />').attr ('class', '_c'));
    },
    show: function (url) {

      box.$el.empty ().addClass ('s');

      $.get (url)
      .done (function (result) {
        var $b = $('<div />').addClass ('banner').append ($('<img />').attr ('src', result.bg));

        var $l = $('<div />').addClass ('logo').append ($('<img />').attr ('src', result.icon));
        var $i = $('<div />').addClass ('info').append (
                  $('<b />').text (result.name)).append (
                  $('<span />').text ('營業時間：' + result.open_time)).append (
                  $('<span />').text ('電話：' + result.phone)).append (
                  $('<span />').text ('地址：' + result.address));

        var $d = $('<div />').addClass ('desc').text (result.content);
        var $s = $('<div />').addClass ('details').append ($('<div />').addClass ('icon-6').append ($('<b />').text ('飲食類型')).append ($('<span />').text (result.type)))
                                                  .append ($('<div />').addClass ('icon-7').append ($('<b />').text ('荷包情報')).append ($('<span />').text (result.money)))
                                                  .append ($('<div />').addClass ('icon-8').append ($('<b />').text ('菜單推薦')).append ($('<span />').text (result.menu)));
        var $m = result.imgs.map (function (t) { return $('<div />').data ('ori', t.ori).append ($('<img />').attr ('src', t.min)).imgLiquid ({ verticalAlign: 'center' }).click (function () {
          var $img = $('<img />').attr ('src', $(this).data ('ori'));
          box.$el2.empty ().addClass ('s');
          var $div = $(this);

          $img.load (function () {
            var w = $img.get (0).width, h = $img.get (0).height;
            var mx = $(window).width () > 640 ? 640 : $(window).width ();
            if (w > mx - 32) { h = h / w * (mx - 32); w = mx - 32; }
            if (h > $(window).height () - 32) { w = w / h * ($(window).height () - 32); h = $(window).height () - 32; }
            var l = 'calc(50% - ' + (w / 2) + 'px)', t = 'calc(50% - ' + (h / 2) + 'px)';

            $img = $('<figure />').append ($('<img />').attr ('src', $div.data ('ori')));
            box.$el2.css ({ top: t, left: l, width: w + 'px', height: h + 'px' }).append ($img).append ($('<a />').addClass ('icon-5 close').click (function () { box.$el2.empty ().removeClass ('s'); }));
            $img.imgLiquid ({verticalAlign: 'center'});
          });
        }); });

        box.$el.append ($('<div />').addClass ('icon-5 close').click (box.close))
                .append ($('<div />').addClass ('hot'))
                .append ($('<div />').addClass ('content').append ($b).append ($('<div />').addClass ('top').append ($l).append ($i)).append ($d).append ($s).append ($('<div />').addClass ('imgs')
                             .append ($m)));

        $b.imgLiquid ({ verticalAlign: 'center' });
        $l.imgLiquid ({ verticalAlign: 'center' });
      })
      .fail (box.close);

    },
    close: function () {
      box.$el.empty ().removeClass ('s');
    }
  };
  box.init ();

  $('.box').each (function () {
    var $that = $(this);
    $that.click (function () {
      if ($(this).data ('box')) {
        box.show ($(this).data ('box'));
        recordClickCnt($that, '');
      }
      else if ($(this).data ('link')) {
        recordClickCnt( $that, function() {
          window.location.assign( $(this).data('link') );
        }.bind( $(this) ) ) ;
      }

      else;
    });
  });

  $('#start-img, #mid > a').each (function () {
    $(this).imgLiquid ({ verticalAlign: 'center' });
  });

  $('#top, #bot').each (function () {
    var $that = $(this);
    $that.data ('l', $that.find ('>.img').length);
    $that.attr ('data-i', 1);
    setInterval (function () { var l = parseInt ($that.data ('l'), 10), i = parseInt ($that.attr ('data-i'), 10); $that.attr ('data-i', ++i > l ? 1 : i); }, (Math.floor ((Math.random () * 5) + 4)) * 1000);
    $that.find ('.box').imgLiquid ({ verticalAlign: 'center' });
  });

  var $header = $('#header');
  var $list = $('#list');
  $('#sf').click (function () { $header.toggleClass ('f'); });
  $('#st').click (function () { $header.toggleClass ('t'); });
  // $('.logo, .ad').imgLiquid ({ fill: true });


  $('#banner').each (function () {
    var $that = $(this).attr ('data-i', 1);
    var l = $that.find ('.banner > *').length - 4;
    var end = 1+l;

    $that.find ('>a').click (function () {
      var start = parseInt ($that.attr ('data-i'), 10);
      $that.attr('data-i', $(this).hasClass('l') ? ( start > 1 ? --start : start ) : ( start < end ? ++start : start ) );
    });

    setTimeout (function () { $that.addClass ('ani'); }, 300);
  });
});

function recordClickCnt (obj, callback) {
  $.post (obj.data('ajax'), { table: obj.data('table'), id: obj.data('id') })
  .complete (callback);
}
