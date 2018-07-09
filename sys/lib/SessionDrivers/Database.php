<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class SessionDatabaseDriver extends SessionDriver implements SessionHandlerInterface {
  private $model;
  private $rowExists = false;

  public function __construct ($cookie) {
    parent::__construct ($cookie);

    use_model () || gg ('[Session] SessionDatabaseDriver 錯誤，無法連線資料庫。');
    $this->config['model'] && class_exists ($this->config['model']) || gg ('[Session] SessionDatabaseDriver 錯誤，找不到指定的 Model。Model：' . $this->config['model']);

    $model = $this->config['model'];
    ($obj = ModelConnection::instance ()->query ("SHOW TABLES LIKE '" . $model::$table_name . "';")->fetch (PDO::FETCH_NUM)) && ($obj[0] == $model::$table_name) || $this->createTable ($model);
    $this->model = $model;
    ini_set ('session.save_path', $this->model);
  }

  private function query ($sql) {
    try {
      ModelConnection::instance ()->query ($sql);
      return '';
    } catch (Exception $e) {
      return $e->getMessage ();
    }
  }
  private function createTable ($model) {
    ($database = config ('database')) && isset ($database['active_group']) && ($active = $database['active_group']) && isset ($database['groups'][$active]['char_set']) && ($char_set = $database['groups'][$active]['char_set']) && isset ($database['groups'][$active]['dbcollat']) && ($dbcollat = $database['groups'][$active]['dbcollat']) || gg ('[Session] SessionDatabaseDriver createTable 錯誤，Database Config 錯誤。');

    $sql = "CREATE TABLE `" . $model::$table_name . "` ("
            . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
            . "`session_id` varchar(128) COLLATE " . $dbcollat . " NOT NULL DEFAULT '' COMMENT 'Session ID',"
            . "`ip_address` varchar(45) COLLATE " . $dbcollat . " NOT NULL DEFAULT '' COMMENT 'IP',"
            . "`timestamp` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Timestamp',"
            . "`data` blob NOT NULL COMMENT 'Data',"
            . "PRIMARY KEY (`id`),"
            . "KEY `ip_address_session_id_index` (`ip_address`,`session_id`),"
            . "KEY `session_id_index` (`session_id`)"
          . ") ENGINE=InnoDB DEFAULT CHARSET=" . $char_set . " COLLATE=" . $dbcollat . ";";
 
    ($err = $this->query ($sql)) && gg ('[Session] SessionDatabaseDriver createTable 錯誤，建置 Table 失敗。SQL：' . $sql . '，Error：' . $err);

    return true;
  }
  public function open ($model, $name) {
    if (!($model && class_exists ($model)))
      gg ('[Session] SessionDatabaseDriver 錯誤，找不到指定的 Model。Model：' . $model);

    $this->model = $this->config['model'] = $model;
    return $this->succ ();
  }

  public function read ($session_id) {
    $model = $this->model;

    if ($this->getLock ($session_id) !== false) {
      $this->sessionId = $session_id;

      if (!$obj = $model::find ('first', array ('select' => 'data', 'conditions' => $this->config['match_ip'] ? array ('session_id = ? AND ip = ?', $session_id, $_SERVER['REMOTE_ADDR']) : array ('session_id = ?', $session_id)))) {
        $this->rowExists = false;
        $this->fingerprint = md5 ('');
        return '';
      }

      $result = $obj->data;
      $this->fingerprint = md5 ($result);
      $this->rowExists = true;
      return $result;
    }

    $this->fingerprint = md5 ('');
    return '';
  }

  public function write ($session_id, $session_data) {
    $model = $this->model;

    if ($session_id !== $this->sessionId) {
      if (!$this->releaseLock () || !$this->getLock ($session_id))
        return $this->fail();

      $this->rowExists = false;
      $this->sessionId = $session_id;
    } else if ($this->lock === false) {
      return $this->fail ();
    }

    if ($this->rowExists === false) {
      if ($model::create (array ('session_id' => $session_id, 'ip_address' => $_SERVER['REMOTE_ADDR'], 'timestamp' => time (), 'data' => $session_data))) {
        $this->fingerprint = md5 ($session_data);
        $this->rowExists = true;
        return $this->succ ();
      }

      return $this->fail ();
    }

    if (!$obj = $model::find ('first', array ('select' => 'id, data, timestamp', 'conditions' => $this->config['match_ip'] ? array ('session_id = ? AND ip = ?', $session_id, $_SERVER['REMOTE_ADDR']) : array ('session_id = ?', $session_id))))
      return $this->fail ();

    $obj->timestamp = time ();

    if ($this->fingerprint !== md5 ($session_data))
      $obj->data = $session_data;

    if ($obj->save ()) {
      $this->fingerprint = md5 ($session_data);
      return $this->succ ();
    }

    return $this->fail ();
  }

  public function close () {
    return ($this->lock && !$this->releaseLock ()) ? $this->fail() : $this->succ ();
  }

  public function destroy ($session_id) {
    $model = $this->model;

    if ($this->lock)
      if (!(($obj = $model::find ('first', array ('select' => 'id, data, timestamp', 'conditions' => $this->config['match_ip'] ? array ('session_id = ? AND ip = ?', $session_id, $_SERVER['REMOTE_ADDR']) : array ('session_id = ?', $session_id)))) && $obj->destroy ()))
        return $this->fail ();

    if ($this->close () === $this->succ ()) {
      $this->cookieDestroy ();
      return $this->succ ();
    }

    return $this->fail ();
  }

  public function gc ($maxlifetime) {
    $model = $this->model;
    return $model::delete_all (array('conditions' => array('timestamp < ?', time () - $maxlifetime))) ? $this->succ () : $this->fail ();
  }

  protected function getLock ($session_id) {
    $model = $this->model;
    $arg = md5 ($session_id . ($this->config['match_ip'] ? '_' . $_SERVER['REMOTE_ADDR'] : ''));

    if (($obj = $model::query ('SELECT GET_LOCK("' . $arg . '", 300) AS session_lock')->fetch ()) && $obj['session_lock']) {
      $this->lock = $arg;
      return true;
    }
    return false;
  }

  protected function releaseLock () {
    if (!$this->lock)
      return true;

    $model = $this->model;
    if (($obj = $model::query ('SELECT RELEASE_LOCK("' . $this->lock . '") AS session_lock')->fetch ()) && $obj['session_lock']) {
      $this->lock = false;
      return true;
    }

    return false;
  }
}
