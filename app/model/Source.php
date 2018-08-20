<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Source extends Model {
  static $table_name = 'sources';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
    array('card', 'class_name' => 'Card')
  );

  const TYPE_USER    = 'user';
  const TYPE_GROUP   = 'group';
  const TYPE_ROOM    = 'room';
  const TYPE_OTHER   = 'other';

  static $typeNames = array (
    self::TYPE_USER   => '使用者',
    self::TYPE_GROUP  => '群組',
    self::TYPE_ROOM   => '聊天室',
    self::TYPE_OTHER  => '其他',
  );

  public function __construct ($attrs = array (), $guardAttrs = true, $instantiatingViafind = false, $newRecord = true) {
    parent::__construct ($attrs, $guardAttrs, $instantiatingViafind, $newRecord);

  }

  public function destroy () {
    if (!isset ($this->id))
      return false;

    return $this->delete ();
  }

  public static function isCurrentCard($sid, $cardId) {
    if(($obj = Source::find_by_sid($sid)) && $obj->card_id == $cardId)
      return true;
    return false;
  }

  public static function getType($event) {
    if( $event->isUserEvent() ) return Source::TYPE_USER;
    if( $event->isGroupEvent() ) return Source::TYPE_GROUP;
    if( $event->isRoomEvent() ) return Source::TYPE_ROOM;
    return Source::TYPE_OTHER;
  }

  public static function getTitle($event) {
    Load::lib ('MyLineBot.php');

    $response = MyLineBot::bot()->getProfile($event->getUserId());
    if ( $response->isSucceeded() && $profile = $response->getJSONDecodedBody() )
        return $profile['displayName'];
    return '查無姓名';
  }

  public static function checkSourceExist($event) {
    if( !$sid = $event->getEventSourceId() )
      return false;

    if( !$obj = Source::find('one', array('where' => array('sid = ?', $sid) ) ) ) {
      $param = array(
        'sid' => $sid,
        'title' => Source::getTitle($event),
        'type' => self::getType($event),
      );

      if(($richmenus = RichMenu::getMenuList()) && $richMenuId = $richmenus['richmenus'][0]['richMenuId']) {
        Load::lib('MyLineBot.php');
        if(!RichMenu::linkToUser($sid, $richMenuId))
          return false;
      }

      $transaction = function() use (&$obj, $param){ 
        return $obj = Source::create( $param );
      };
      if( !Source::transaction( $transaction, $obj, $param ) )
        return false;
    }

    return $obj;
  }

  public static function checkSpeakerExist($event) {
    if( !($userId = $event->getUserId()) )
      return false;

    if( !$obj = Source::find('one', array('where' => array('sid = ?', $userId) ) ) ) {
      $param = array(
        'sid' => $userId,
        'title' => Source::getTitle($event),
        'type' => Source::TYPE_USER,
      );
      $transaction = function() use (&$obj, $param){
        return $obj = Source::create( $param );
      };
      if( !Source::transaction( $transaction, $obj, $param ) )
        return false;
    }

    return $obj;
  }

  //儲存個人處理程序
  public function saveProcess($text) {
    $process = json_decode($this->process, true);
    if( empty($process) || empty($text) )
      return false;

    if( $process['date'] && strtotime('today') > strtotime('+1 week', strtotime($process['date'])) ) {
      $process = '';
    } else {
      $process['content'] .= $text . "\r\n";
      $process = json_encode($process);
    }
    $this->process = $process;
    $this->save();
  }
}
