<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class CacheFileDriver {
  private $path;
  private $prefix = '';

  public function __construct ($config) {
    $config = array_merge (config ('cache', 'drivers', 'file'), $config);
    isset ($config['prefix']) && $this->prefix = $config['prefix'];
    $this->path = $config['path'];
    $this->isSupported () || gg ('[Cache] CacheFileDriver 錯誤，路徑無法寫入。');

    Load::sysFunc ('file.php');
  }

  public function get ($id) {
    return ($data = $this->_get ($id)) !== null ? is_array ($data) ? $data['data'] : $data : null;
  }

  private function _get ($id) {
    if (!is_file ($this->path . $this->prefix . $id))
      return null;

    $data = unserialize (read_file ($this->path . $this->prefix . $id));

    if (!($data['ttl'] > 0 && time () > $data['time'] + $data['ttl']))
      return $data;

    unlink ($this->path . $this->prefix . $id);
    return null;
  }

  public function save ($id, $data, $ttl = 60) {
    $contents = array (
      'time' => time (),
      'ttl' => $ttl,
      'data' => $data
    );

    if (!write_file ($this->path . $this->prefix . $id, serialize ($contents)))
      return false;

    chmod ($this->path . $this->prefix . $id, 0640);
    return true;
  }

  public function delete ($id) {
    return is_file ($this->path . $this->prefix . $id) ? unlink ($this->path . $this->prefix . $id) : false;
  }

  public function clean () {
    return delete_files ($this->path, false, true);
  }

  public function info () {
    return get_dir_file_info ($this->path);
  }

  public function metadata ($id) {
    if (!is_file ($this->path . $this->prefix . $id))
      return null;

    $data = unserialize (file_get_contents ($this->path . $this->prefix . $id));

    if (!is_array ($data))
      return null;

    $mtime = filemtime ($this->path . $this->prefix . $id);

    return !isset ($data['ttl'], $data['time']) ? false : array (
      'expire' => $data['time'] + $data['ttl'],
      'mtime'  => $mtime
    );
  }

  public function isSupported () {
    return is_really_writable ($this->path);
  }
}
