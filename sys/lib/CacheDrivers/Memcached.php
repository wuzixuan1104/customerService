<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class CacheMemcachedDriver {
  private $memcached = null;
  private $prefix = '';

  public function __construct ($config = array ()) {
    $config = array_merge (config ('cache', 'drivers', 'memcached'), $config);
    isset ($config['prefix']) && $this->prefix = $config['prefix'];

    $this->isSupported () || gg ('[Cache] CacheMemcachedDriver 錯誤，載入 Memcached 失敗。');

    $this->memcached = class_exists ('Memcached', false) ? new Memcached () : class_exists ('Memcache', false);
    $this->memcached->setOption (Memcached::OPT_BINARY_PROTOCOL, true);

    foreach ($config['servers'] as $server)
      if ($this->memcached instanceof Memcached)
        $this->memcached->addServer ($server['host'], $server['port'], $server['weight']);
      else
        $this->memcached->addServer ($server['host'], $server['port'], true, $server['weight']);
  }

  public function isSupported () {
    return extension_loaded ('memcached') || extension_loaded ('memcache');
  }

  public function get ($id) {
    return ($data = $this->memcached->get ($this->prefix . $id)) === false ? null : unserialize ($data);
  }

  public function save ($id, $data, $ttl = 60) {
    return ($this->memcached instanceof Memcached ? $this->memcached->set ($this->prefix . $id, serialize ($data), $ttl) : $this->memcached->set ($this->prefix . $id, $data, 0, $ttl)) ? true : false;
  }

  public function delete ($id) {
    return $this->memcached->delete ($this->prefix . $id);
  }

  public function clean () {
    if (!$this->prefix)
      return $this->memcached->flush ();

    if (!$keys = $this->memcached->getAllKeys ())
      return true;

    $regex = $this->prefix . '.*';

    foreach($keys as $item)
      if (preg_match ('/' . $regex . '/', $item))
        $this->memcached->delete ($item);
 
    return true;
  }

  public function info () {
    return $this->memcached->getStats ();
  }

  public function metadata ($id) {
    if (($metadata = $this->memcached->get ($this->prefix . $id)) === false || count ($metadata) !== 3)
      return null;

    list ($data, $time, $ttl) = $metadata;

    return array (
      'expire' => $time + $ttl,
      'mtime' => $time,
      'data' => $data
    );
  }

  public function __destruct () {
    return $this->memcached instanceof Memcached ? $this->memcached->quit () : $this->memcached->close ();
  }
}
