<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class SessionMemcachedDriver extends SessionDriver implements SessionHandlerInterface {
  private $memcached = null;
  private $keyPrefix = 'oaci_session:';
  private $expiration = 7200; 
  private $lockKey   = null; 
  private $servers   = array ();
  
  public function __construct ($cookie, $expiration) {
    parent::__construct ($cookie);

    extension_loaded ('memcached') && !extension_loaded ('memcache') || gg ('[Session] SessionMemcachedDriver 錯誤，載入 Memcached 失敗。');
    isset ($this->config['prefix']) && is_string ($this->config['prefix']) && $this->keyPrefix = $this->config['prefix'] . ($this->config['match_ip'] ? $_SERVER['REMOTE_ADDR'] . ':' : '');
    ((isset ($this->config['servers']) && is_array ($this->config['servers']) && ($this->servers = $this->config['servers'])) || (isset ($this->config['server']) && is_array ($this->config['server']) && ($this->servers = array ($this->config['server'])))) && ($this->servers = array_filter (array_map (function ($server) { if (!(isset ($server['host']) && isset ($server['port']) && is_string ($server['host']) && is_string ($server['port']) && $server['host'] && $server['port'])) return null; $server['host'] = isset ($server['host']) && is_numeric ($server['host']) ? $server['host'] : 0; return $server; }, $this->servers))) || gg ('[Session] SessionMemcachedDriver 錯誤，至少需要一項 Servers。');
    $this->expiration = $expiration;
  }

  public function open ($save_path, $name) {
    $memcached = class_exists ('Memcached', false) ? new Memcached () : class_exists ('Memcache', false);

    $memcached->setOption (Memcached::OPT_BINARY_PROTOCOL, true);
    $servers = array_map (function ($server) { return $server['host'] . ':' . $server['port']; }, $memcached->getServerList ());

    foreach ($this->servers as $server) {
      if (in_array ($server['host'] . ':' . $server['port'], $servers, true))
        continue;
 
      if ($memcached instanceof Memcached ? $memcached->addServer ($server['host'], $server['port'], $server['weight']) : $memcached->addServer ($server['host'], $server['port'], true, $server['weight']))
        array_push ($servers, $server['host'] . ':' . $server['port']);
    }

    if (!$servers)
      gg ('[Session] SessionMemcachedDriver 錯誤，至少需要一項 Servers。');

    $this->memcached = $memcached;

    return $this->succ ();
  }

  public function read ($session_id) {
    if ($this->memcached && $this->getLock ($session_id)) {
      $this->sessionId = $session_id;
      $data = (string) $this->memcached->get ($this->keyPrefix . $session_id);
      $this->fingerprint = md5 ($data);
      return $data;
    }

    return $this->fail ();
  }

  public function write ($session_id, $session_data) {
    if (!($this->memcached && $this->lockKey))
      return $this->fail ();

    if ($session_id !== $this->sessionId) {
      if (!$this->releaseLock () || !$this->getLock ($session_id))
        return $this->fail();

      $this->fingerprint = md5 ('');
      $this->sessionId = $session_id;
    }

    $key = $this->keyPrefix . $session_id;

    $this->memcached->replace ($this->lockKey, time (), 300);

    if ($this->fingerprint !== ($fingerprint = md5 ($session_data))) {
      
      if ($this->memcached instanceof Memcached ? $this->memcached->set ($key, $session_data, $this->expiration) : $this->memcached->set ($key, $session_data, 0, $this->expiration)) {
        $this->fingerprint = $fingerprint;
        return $this->succ ();
      }

      return $this->fail ();
    }
    
    if ($this->memcached->touch ($key, $this->expiration) || ($this->memcached->getResultCode () === Memcached::RES_NOTFOUND && $this->memcached->set ($key, $session_data, $this->expiration)))
      return $this->succ ();

    return $this->fail ();
  }

  public function close () {
    if (!$this->memcached)
      return $this->succ ();

    $this->releaseLock ();
    if (!($memcached instanceof Memcached ? $this->memcached->quit () : $this->memcached->close ()))
      return $this->fail ();

    $this->memcached = null;
    return $this->succ ();
  }

  public function destroy ($session_id) {
    if (!($this->memcached && $this->lockKey))
      return $this->fail();
    
    $this->memcached->delete ($this->keyPrefix . $session_id);
    $this->cookieDestroy ();
    return $this->succ ();
  }

  public function gc ($maxlifetime) {
    return $this->succ ();
  }

  protected function getLock ($session_id) {
    if ($this->lockKey === $this->keyPrefix . $session_id . ':lock')
      if (!$this->memcached->replace ($this->lockKey, time (), 300))
        return $this->memcached->getResultCode () === Memcached::RES_NOTFOUND ? $this->memcached->add ($this->lockKey, time (), 300) : false;

    $attempt = 0;
    $lockKey = $this->keyPrefix . $session_id . ':lock';

    do {
      if ($this->memcached->get ($lockKey)) {
        sleep (1);
        continue;
      }

      $method = $this->memcached->getResultCode () === Memcached::RES_NOTFOUND ? 'add' : 'set';

      if (!$this->memcached->$method ($lockKey, time (), 300))
        return false;

      $this->lockKey = $lockKey;
      break;
    } while (++$attempt < 30);

    if ($attempt === 30)
      return false;

    
    return $this->lock = true;
  }

  protected function releaseLock () {
    if ($this->memcached && $this->lock) {
      if (!$this->memcached->delete ($this->lockKey) && $this->memcached->getResultCode () !== Memcached::RES_NOTFOUND)
        return false;

      $this->lockKey = null;
      $this->lock = false;
    }

    return true;
  }
}
