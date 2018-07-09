<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class SessionFileDriver extends SessionDriver implements SessionHandlerInterface {
  private $sidRegexp;
  private $path;
  private $handle;
  private $fileNew;

  public function __construct ($cookie, $expiration, $sidRegexp) {
    parent::__construct ($cookie);

    $this->sidRegexp = $sidRegexp;
    isset ($this->config['path']) && is_dir ($this->config['path']) && is_really_writable ($this->config['path']) || gg ('[Session] SessionFileDriver 錯誤，路徑不存在或無法寫入。Path：' . $this->config['path']);
    ini_set ('session.save_path', $this->config['path']);
  }

  public function open ($path, $name) {
    if (!(isset ($path) && is_dir ($path) && is_really_writable ($path)))
      gg ('[Session] SessionFileDriver 錯誤，路徑不存在或無法寫入。Path：' . $path);

    $this->config['path'] = $path;
    $this->path = $this->config['path'] . $name . '_' . ($this->config['match_ip'] ? md5 ($_SERVER['REMOTE_ADDR']) : '');

    return $this->succ ();
  }

  public function read ($session_id) {
    if ($this->handle === null) {
      $this->fileNew = !file_exists ($this->path . $session_id);

      if (($this->handle = fopen ($this->path . $session_id, 'c+b')) === false)
        return $this->fail ();

      if (flock ($this->handle, LOCK_EX) === false) {
        fclose ($this->handle);
        $this->handle = null;
        return $this->fail ();
      }

      $this->sessionId = $session_id;

      if ($this->fileNew) {
        chmod ($this->path . $session_id, 0600);
        $this->fingerprint = md5 ('');
        return '';
      }
    } else if ($this->handle === false) {
      return $this->fail ();
    } else {
      rewind ($this->handle);
    }

    $data = '';
    for ($read = 0, $length = filesize ($this->path . $session_id); $read < $length; $read += Charset::strlen ($buffer)) {
      if (($buffer = fread ($this->handle, $length - $read)) === false)
        break;

      $data .= $buffer;
    }

    $this->fingerprint = md5 ($data);
    return $data;
  }

  public function write ($session_id, $session_data) {
    if ($session_id !== $this->sessionId && ($this->close () === $this->fail () || $this->read ($session_id) === $this->fail ()))
      return $this->fail ();

    if (!is_resource ($this->handle))
      return $this->fail ();
    
    if ($this->fingerprint === md5 ($session_data))
      return !$this->fileNew && !touch ($this->path . $session_id) ? $this->fail () : $this->succ ();

    if (!$this->fileNew) {
      ftruncate ($this->handle, 0);
      rewind ($this->handle);
    }

    if (($length = strlen ($session_data)) > 0) {
      for ($written = 0; $written < $length; $written += $result)
        if (($result = fwrite ($this->handle, substr ($session_data, $written))) === false)
          break;

      if (!is_int ($result)) {
        $this->fingerprint = md5 (substr ($session_data, 0, $written));
        return $this->fail ();
      }
    }

    $this->fingerprint = md5 ($session_data);
    return $this->succ ();
  }

  public function close () {
    if (is_resource ($this->handle)) {
      flock ($this->handle, LOCK_UN);
      fclose ($this->handle);

      $this->handle = $this->fileNew = $this->sessionId = null;
    }

    return $this->succ ();
  }

  public function destroy ($session_id) {
    if ($this->close() === $this->succ ()) {
      if (file_exists ($this->path . $session_id)) {
        $this->cookieDestroy ();

        return unlink ($this->path . $session_id) ? $this->succ () : $this->fail ();
      }

      return $this->succ ();
    }

    if ($this->path !== null) {
      clearstatcache ();

      if (file_exists ($this->path . $session_id)) {
        $this->cookieDestroy ();
        return unlink ($this->path . $session_id) ? $this->succ () : $this->fail ();
      }

      return $this->succ ();
    }

    return $this->fail ();
  }

  public function gc ($maxlifetime) {
    if (!is_dir ($this->config['path']) || ($directory = opendir ($this->config['path'])) === false)
      return $this->fail ();

    $ts = time () - $maxlifetime;

    $pattern = ($this->config['match_ip'] === true) ? '[0-9a-f]{32}' : '';
    $pattern = sprintf ('#\A%s' . $pattern . $this->sidRegexp . '\z#', preg_quote ($this->cookie['name']));

    while (($file = readdir ($directory)) !== false) {
      if (!preg_match ($pattern, $file) || !is_file ($this->config['path'] . DIRECTORY_SEPARATOR . $file) || ($mtime = filemtime ($this->config['path'] . DIRECTORY_SEPARATOR . $file)) === false || $mtime > $ts)
        continue;
      unlink ($this->config['path'] . DIRECTORY_SEPARATOR . $file);
    }

    closedir ($directory);
    return $this->succ ();
  }
}
