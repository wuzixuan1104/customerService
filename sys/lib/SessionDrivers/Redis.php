<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class SessionRedisDriver extends SessionDriver implements SessionHandlerInterface {
  private $redis     = null; 
  private $keyPrefix = 'oaci_session:'; 
  private $expiration = 7200; 
  private $lockKey   = null; 
  private $keyExists = false; 

  public function __construct ($cookie, $expiration) {
    parent::__construct ($cookie);

    extension_loaded ('redis') || gg ('[Session] SessionRedisDriver 錯誤，載入 Redis 失敗。');
    is_string ($this->config['host']) && $this->config['host'] && is_string ($this->config['port']) && $this->config['port'] || gg ('[Session] SessionRedisDriver 錯誤，未設定 Host 與 Port。Host：' . $this->config['host'] . '，Port：' . $this->config['port']);

    isset ($this->config['password']) && is_string ($this->config['password']) || $this->config['password'] = '';
    isset ($this->config['database']) && is_numeric ($this->config['database']) || $this->config['database'] = null;
    isset ($this->config['timeout']) && is_float ($this->config['timeout']) || $this->config['timeout'] = null;
    isset ($this->config['prefix']) && is_string ($this->config['prefix']) && $this->keyPrefix = $this->config['prefix'] . ($this->config['match_ip'] ? $_SERVER['REMOTE_ADDR'] . ':' : '');
  
    $this->expiration = $expiration;
  }

  public function open ($save_path, $name) {
    $redis = new Redis ();

    if (!$redis->connect ($this->config['host'], $this->config['port'], $this->config['timeout']))
      gg ('[Session] SessionRedisDriver 錯誤，連不上 Redis。Host：' . $this->config['host'] . '，Port：' . $this->config['port'] . '，Timeout：' . $this->config['timeout']);

    if ($this->config['password'] && !$redis->auth ($this->config['password']))
      gg ('[Session] SessionRedisDriver 錯誤，請確認密碼。Password：' . $this->config['password']);

    if ($this->config['database'] && !$redis->select ($this->config['database']))
      gg ('[Session] SessionRedisDriver 錯誤，找不到指定的 Database，Database：' . $this->config['database']);
    
    $this->redis = $redis;
    return $this->succ ();
  }

  public function read ($session_id) {
    if ($this->redis && $this->getLock ($session_id)) {
      $this->sessionId = $session_id;
      $data = $this->redis->get ($this->keyPrefix . $session_id);
      is_string ($data) ? $this->keyExists = true : $data = '';
      $this->fingerprint = md5 ($data);
      return $data;
    }

    return $this->fail ();
  }

  public function write ($session_id, $session_data) {
    if (!($this->redis && $this->lockKey))
      return $this->fail ();
    
    if ($session_id !== $this->sessionId) {
      if (!($this->releaseLock () && $this->getLock ($session_id)))
        return $this->fail ();

      $this->keyExists = false;
      $this->sessionId = $session_id;
    }

    $this->redis->setTimeout ($this->lockKey, 300);
    if ($this->fingerprint !== ($fingerprint = md5 ($session_data)) || $this->keyExists === false) {
      
      if ($this->redis->set ($this->keyPrefix . $session_id, $session_data, $this->expiration)) {
        $this->fingerprint = $fingerprint;
        $this->keyExists = true;
        return $this->succ ();
      }

      return $this->fail ();
    }
    return $this->redis->setTimeout ($this->keyPrefix . $session_id, $this->expiration) ? $this->succ () : $this->fail();
  }

  public function close () {
    if (!isset ($this->redis))
      return $this->succ ();

    try {
      if ($this->redis->ping () === '+PONG') {
        $this->releaseLock ();

        if ($this->redis->close () === false)
          return $this->fail ();
      }
    } catch (RedisException $e) {
      Log::message ('error' . 'Session: Got RedisException on close(): ' . $e->getMessage ());
    }

    $this->redis = null;

    return $this->succ ();
  }

  public function destroy ($session_id) {
    if (!($this->redis && $this->lockKey))
      return $this->fail ();

    $this->redis->delete ($this->keyPrefix . $session_id);
    $this->cookieDestroy ();
    return $this->succ ();
  }

  public function gc ($maxlifetime) {
    return $this->succ ();
  }

  protected function getLock ($session_id) {
    if ($this->lockKey === $this->keyPrefix . $session_id . ':lock')
      return $this->redis->setTimeout ($this->lockKey, 300);

    $attempt = 0;
    $lockKey = $this->keyPrefix . $session_id.':lock';

    do {
      if (($ttl = $this->redis->ttl ($lockKey)) > 0) {
        sleep (1);
        continue;
      }

      if (!$result = ($ttl === -2) ? $this->redis->set ($lockKey, time (), array ('nx', 'ex' => 300)) : $this->redis->setex ($lockKey, 300, time ()))
        return false;

      $this->lockKey = $lockKey;

      break;
    } while (++$attempt < 30);

    if ($attempt === 30)
      return false;

    return $this->lock = true;
  }

  protected function releaseLock () {
    if ($this->redis && $this->lockKey && $this->lock) {
      if (!$this->redis->delete ($this->lockKey))
        return false;

      $this->lockKey = null;
      $this->lock = false;
    }

    return true;
  }
}
