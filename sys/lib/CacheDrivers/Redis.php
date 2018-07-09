<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class CacheRedisDriver {
  private $redis = null;
  private $prefix = '';
  private $serializeKey = '_oaci_redis_serialized';

  public function __construct ($config = array ()) {
    $config = array_merge (config ('cache', 'drivers', 'redis'), $config);
    isset ($config['prefix']) && $this->prefix = $config['prefix'];

    $this->isSupported () || gg ('[Cache] CacheRedisDriver 錯誤，載入 Redis 失敗。');

    $redis = new Redis ();
    $redis->connect ($config['host'], $config['port'], $config['timeout']) || gg ('[Cache] CacheRedisDriver 錯誤，連不上 Redis。Host：' . $config['host'] . '，Port：' . $config['port'] . '，Timeout：' . $config['timeout']);

    $config['password'] && !$redis->auth ($config['password']) && gg ('[Cache] CacheRedisDriver 錯誤，請確認密碼。Password：' . $config['password']);
    $config['database'] && !$redis->select ($config['database']) &&  gg ('[Cache] CacheRedisDriver 錯誤，找不到指定的 Database，Database：' . $config['database']);
    
    $this->redis = $redis;

    $serialized = $this->redis->sMembers ($this->prefix . $this->serializeKey);
    empty ($serialized) || $this->serialized = array_flip ($serialized);
  }

  public function isSupported () {
    return extension_loaded ('redis');
  }

  public function get ($id) {
    if (($value = $this->redis->get ($this->prefix . $id)) === false)
      return null;

    if (isset ($this->serialized[$this->prefix . $id]))
      return unserialize ($value);

    return $value;
  }

  public function save ($id, $data, $ttl = 60) {
    $id = $this->prefix . $id;
    if (is_array ($data) || is_object ($data)) {
      if (!$this->redis->sIsMember ($this->prefix . $this->serializeKey, $id) && !$this->redis->sAdd ($this->prefix . $this->serializeKey, $id))
        return false;

      isset ($this->serialized[$id]) || $this->serialized[$id] = true;
      $data = serialize ($data);
    } else if (isset($this->serialized[$id])) {
      $this->serialized[$id] = null;
      $this->redis->sRemove ($this->prefix . $this->serializeKey, $id);
    }

    return $this->redis->set ($id, $data, $ttl) ? true : false;
  }

  public function delete ($id) {
    $id = $this->prefix . $id;
    if ($this->redis->delete ($id) !== 1)
      return false;

    if (isset ($this->serialized[$id])) {
      $this->serialized[$id] = null;
      $this->redis->sRemove ($this->prefix . $this->serializeKey, $id);
    }

    return true;
  }

  public function clean () {
    if (!$this->prefix)
      return $this->redis->flushDB ();

    if ($keys = $this->redis->keys ($this->prefix . '*'))
      foreach ($keys as $key)
        $this->redis->delete ($keys);
    
    return true;
  }

  public function info () {
    return $this->redis->info();
  }

  public function metadata ($key) {
    if (($value = $this->get ($key)) === false)
      return null;

    return array (
      'expire' => time () + $this->redis->ttl ($key),
      'data' => $value
    );
  }

  public function __destruct () {
    $this->redis && $this->redis->close ();
  }
}
