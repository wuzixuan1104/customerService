<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Session {
  private static $driver;
  private static $cookie;
  private static $config;
  private static $time2Update;
  private static $regenerateDestroy;
  private static $expiration;
  private static $sidRegexp;

  public static function init () {
    if (request_is_cli ())
      return;

    if ((bool) ini_get ('session.auto_start'))
      return;

    $session = config ('session');

    self::$driver = $session['driver'];
    self::$time2Update = $session['time_to_update'];
    self::$regenerateDestroy = $session['regenerate_destroy'];
    self::$expiration = $session['expiration'];
    isset ($session['drivers'][$session['driver']]) || gg ('Session Driver 類型錯誤！ Driver：' . $session['driver'] . '，可用類型有：' . implode (', ', array_keys ($session['drivers'])) . '。');

    $cookie = config ('cookie');
    self::$cookie['name'] = $session['cookie_name'];
    self::$cookie['path'] = $cookie['path'];
    self::$cookie['domain'] = $cookie['domain'];
    self::$cookie['secure'] = $cookie['secure'];

    self::configure ();
    Load::sysLib ('SessionDrivers' . DIRECTORY_SEPARATOR . ucfirst (self::$driver) . '.php', 'Session「' . ucfirst (self::$driver) . '」Driver 不存在。');

    $class = 'Session' . ucfirst (self::$driver) . 'Driver';
    $class = new $class (self::$cookie, self::$expiration, self::$sidRegexp);

    if ($class instanceof SessionHandlerInterface) {
      if (is_php ('5.4')) {
        session_set_save_handler ($class, true);
      } else {
        session_set_save_handler (array ($class, 'open'), array ($class, 'close'), array ($class, 'read'), array ($class, 'write'), array ($class, 'destroy'), array ($class, 'gc'));
        register_shutdown_function ('session_write_close');
      }
    } else {
      gg ('Session Driver(' . self::$driver . ') 未遵守 SessionHandlerInterface。');
    }

    if (isset ($_COOKIE[self::$cookie['name']]) && !(is_string ($_COOKIE[self::$cookie['name']]) && preg_match ('#\A' . self::$sidRegexp . '\z#', $_COOKIE[self::$cookie['name']])))
      unset ($_COOKIE[self::$cookie['name']]);

    session_start ();

    if ((empty ($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower ($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') && self::$time2Update > 0) {

      if (!isset ($_SESSION['__oaci_last_regenerate']))
        $_SESSION['__oaci_last_regenerate'] = time ();
      else if ($_SESSION['__oaci_last_regenerate'] < (time () - self::$time2Update))
        self::sessRegenerate (self::$regenerateDestroy);

    } else if (isset ($_COOKIE[self::$cookie['name']]) && $_COOKIE[self::$cookie['name']] === session_id ()) {
      setcookie (self::$cookie['name'], session_id (), self::$expiration ? time () + self::$expiration : 0, self::$cookie['path'], self::$cookie['domain'], self::$cookie['secure'], true);
    }

    self::oaciInitVars ();
  }

  private static function oaciInitVars () {
    isset ($_SESSION['__oaci_vars']) || $_SESSION['__oaci_vars'] = array ();

    if (!$_SESSION['__oaci_vars'])
      return ;

    $current_time = time ();

    foreach ($_SESSION['__oaci_vars'] as $key => &$value) {
      if ($value === 'new')
        $_SESSION['__oaci_vars'][$key] = 'old';
      else if ($value < $current_time)
        unset ($_SESSION[$key], $_SESSION['__oaci_vars'][$key]);
    }

    $_SESSION['__oaci_vars'] || $_SESSION['__oaci_vars'] = array ();
  }

  private static function configure () {
    ini_set ('session.name', self::$cookie['name']);

    session_set_cookie_params (self::$expiration, self::$cookie['path'], self::$cookie['domain'], self::$cookie['secure'], true);

    if (self::$expiration) ini_set ('session.gc_maxlifetime', self::$expiration = (int) self::$expiration);
    else self::$expiration = (int) ini_get('session.gc_maxlifetime');

    // Security is king
    ini_set ('session.use_trans_sid', 0);
    ini_set ('session.use_strict_mode', 1);
    ini_set ('session.use_cookies', 1);
    ini_set ('session.use_only_cookies', 1);

    self::configureSidLength ();
  }

  private static function configureSidLength () {
    if (PHP_VERSION_ID < 70100) {
      $hash_function = ini_get ('session.hash_function');

      if (ctype_digit ($hash_function)) {
        if ($hash_function !== '1')
          ini_set ('session.hash_function', 1);
        $bits = 160;
      } else if (!in_array ($hash_function, hash_algos (), true)) {
        ini_set ('session.hash_function', 1);
        $bits = 160;
      } else if (($bits = strlen (hash ($hash_function, 'dummy', false)) * 4) < 160) {
        ini_set ('session.hash_function', 1);
        $bits = 160;
      }

      $bitsPerCharacter = (int) ini_get ('session.hash_bits_per_character');
      $sidLength        = (int) ceil ($bits / $bitsPerCharacter);
    } else {
      $bitsPerCharacter = (int) ini_get ('session.sid_bits_per_character');
      $sidLength        = (int) ini_get ('session.sid_length');
      
      if (($bits = $sidLength * $bitsPerCharacter) < 160) {
        $sidLength += (int) ceil ((160 % $bits) / $bitsPerCharacter);
        ini_set ('session.sid_length', $sidLength);
      }
    }

    switch ($bitsPerCharacter) {
      case 4: self::$sidRegexp = '[0-9a-f]'; break;
      case 5: self::$sidRegexp = '[0-9a-v]'; break;
      case 6: self::$sidRegexp = '[0-9a-zA-Z,-]'; break;
    }

    self::$sidRegexp .= '{' . $sidLength . '}';
  }

  public static function sessDestroy () {
    session_destroy ();
  }

  public static function sessRegenerate ($destroy = false) {
    $_SESSION['__oaci_last_regenerate'] = time ();
    session_regenerate_id ($destroy);
  }

  public static function allData () {
    return $_SESSION;
  }

  public static function driver () {
    return self::$driver;
  }
  public static function hasData ($key) {
    return isset ($_SESSION[$key]);
  }

// -------------------------------------

  public static function setData ($data, $value) {
    $_SESSION[$data] = $value;
    return true;
  }

  public static function getData ($key) {
    return isset ($_SESSION[$key]) ? $_SESSION[$key] : null;
  }

  public static function unsetData ($key) {
    is_array ($key) || $key = array ($key);

    foreach ($key as $k)
      unset ($_SESSION[$k]);

    return true;
  }

// -------------------------------------

  public static function getFlashDataKeys () {
    if (!isset ($_SESSION['__oaci_vars']))
      return array ();

    return array_values (array_filter (array_keys ($_SESSION['__oaci_vars']), function ($key) {
      return !is_int ($_SESSION['__oaci_vars'][$key]);
    }));
  }

  public static function getFlashDatas () {
    if (!isset ($_SESSION['__oaci_vars']))
      return array ();

    $flashdata = array ();

    if ($_SESSION['__oaci_vars'])
      foreach ($_SESSION['__oaci_vars'] as $key => $value)
        !is_int ($value) && isset ($_SESSION[$key]) && $flashdata[$key] = $_SESSION[$key];

    return $flashdata;
  }

  public static function getFlashData ($key) {
    return isset ($_SESSION['__oaci_vars'], $_SESSION['__oaci_vars'][$key], $_SESSION[$key]) && !is_int ($_SESSION['__oaci_vars'][$key]) ? $_SESSION[$key] : null;
  }

  public static function setFlashData ($data, $value = null) {
    return self::setData ($data, $value) && self::markAsFlash ($data);
  }

  public static function markAsFlash ($key) {
    if (!isset ($_SESSION[$key]))
      return false;

    $_SESSION['__oaci_vars'][$key] = 'new';
    return true;
  }

  public static function keepFlashData ($key) {
    return self::markAsFlash ($key);
  }

  public static function unmarkFlashData ($key) {
    if (!isset ($_SESSION['__oaci_vars']))
      return true;

    is_array ($key) || $key = array ($key);

    foreach ($key as $k)
      if (isset ($_SESSION['__oaci_vars'][$k]) && !is_int ($_SESSION['__oaci_vars'][$k]))
        unset ($_SESSION['__oaci_vars'][$k]);

    $_SESSION['__oaci_vars'] || $_SESSION['__oaci_vars'] = array ();

    return true;
  }

  public static function unsetFlashData ($key) {
    return self::unmarkFlashData ($key) && self::unsetData ($key);
  }

// -------------------------------------

  public static function getTempKeys () {
    if (!isset ($_SESSION['__oaci_vars']))
      return array ();

    return array_values (array_filter (array_keys ($_SESSION['__oaci_vars']), function ($key) {
      return is_int ($_SESSION['__oaci_vars'][$key]);
    }));

    return $keys;
  }

  public static function getTempDatas () {
    $tempdata = array ();

    if ($_SESSION['__oaci_vars'])
      foreach ($_SESSION['__oaci_vars'] as $key => &$value)
        is_int ($value) && $tempdata[$key] = $_SESSION[$key];

    return $tempdata;
  }

  public static function setTempData ($data, $value, $ttl = 300) {
    return self::setData ($data, $value) && self::markAsTemp ($data, $ttl);
  }

  public static function getTempData ($key) {
    return isset ($_SESSION['__oaci_vars'], $_SESSION['__oaci_vars'][$key], $_SESSION[$key]) && is_int ($_SESSION['__oaci_vars'][$key]) ? $_SESSION[$key] : null;
  }

  public static function markAsTemp ($key, $ttl = 300) {
    $ttl += time ();

    if (!isset ($_SESSION[$key]))
      return false;

    $_SESSION['__oaci_vars'][$key] = $ttl;
    return true;
  }

  public static function unmarkTemp ($key) {
    if (!isset ($_SESSION['__oaci_vars']))
      return true;

    is_array ($key) || $key = array ($key);

    foreach ($key as $k)
      if (isset ($_SESSION['__oaci_vars'][$k]) && is_int ($_SESSION['__oaci_vars'][$k]))
        unset ($_SESSION['__oaci_vars'][$k]);

    $_SESSION['__oaci_vars'] || $_SESSION['__oaci_vars'] = array ();

    return true;
  }

  public static function unsetTempData ($key) {
    return self::unmarkTemp ($key) && self::unsetData ($key);
  }
}

if (!interface_exists ('SessionHandlerInterface', false)) {
  interface SessionHandlerInterface {
    public function open ($savePath, $name);
    public function close ();
    public function read ($sessionId);
    public function write ($sessionId, $sessionData);
    public function destroy ($sessionId);
    public function gc ($maxlifetime);
  }
}

abstract class SessionDriver implements SessionHandlerInterface {
  protected $cookie;
  protected $config;
  protected $fingerprint = '';
  protected $sessionId = '';
  protected $lock = false;
  protected $success, $failure;

  public function __construct ($cookie) {
    $this->cookie = $cookie;
    $this->config['match_ip'] = config ('session', 'match_ip');
    ($t = config ('session', 'drivers', Session::driver ())) === null && gg ('Session Config 錯誤; 請檢查 「' . Session::driver () . '」 Config 是否存在。');
    $this->config = array_merge ($this->config, $t);

    if (is_php ('7')) {
      $this->success = true;
      $this->failure = false;
    } else {
      $this->success = 0;
      $this->failure = -1;
    }
  }

  protected function cookieDestroy () { return setcookie ($this->cookie['name'], null, 1, $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], true); }
  protected function getLock ($session_id) { return $this->lock = true; }
  protected function releaseLock () { return !($this->lock && $this->lock = false); }
  protected function succ () { return $this->success; }
  protected function fail () { return $this->failure; }
}

Session::init ();