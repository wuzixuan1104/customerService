<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

if (!function_exists ('now')) {
  function now ($timezone = null) {
    if (empty ($timezone))
      $timezone = config ('other', 'time_reference');

    if ($timezone === 'local' || $timezone === date_default_timezone_get ())
      return time ();

    $datetime = new DateTime ('now', new DateTimeZone ($timezone));
    sscanf ($datetime->format ('j-n-Y G:i:s'), '%d-%d-%d %d:%d:%d', $day, $month, $year, $hour, $minute, $second);

    return mktime ($hour, $minute, $second, $month, $day, $year);
  }
}

if (!function_exists ('mdate')) {
  function mdate ($datestr = '', $time = '') {
    if ($datestr === '')
      return '';
    
    $time || $time = now ();

    return date (str_replace ('%\\', '', preg_replace ('/([a-z]+?){1}/i', '\\\\\\1', $datestr)), $time);
  }
}

if (!function_exists ('standard_date')) {
  function standard_date ($fmt = 'DATE_RFC822', $time = null) {
    $time || $time = now();

    return strpos ($fmt, 'DATE_') !== 0 || defined ($fmt) === false ? false : date (constant ($fmt), $time);
  }
}

if (!function_exists ('timespan')) {
  function timespan ($seconds = 1, $time = '', $units = 7) {
    is_numeric ($seconds) || $seconds = 1;
    is_numeric ($time) || $time = time ();
    is_numeric ($units) || $units = 7;

    $seconds = $time <= $seconds ? 1 : $time - $seconds;

    $str = array ();
    $years = floor ($seconds / 31557600);

    if ($years > 0) array_push ($str, $years . ' 年');
    $seconds -= $years * 31557600;
    $months = floor ($seconds / 2629743);

    if (count ($str) < $units && ($years > 0 || $months > 0)) {
      if ($months > 0) array_push ($str, $months . ' 月');
    }

    $weeks = floor ($seconds / 604800);

    if (count($str) < $units && ($years > 0 || $months > 0 || $weeks > 0)) {
      if ($weeks > 0) array_push ($str, $weeks . ' 週');
      $seconds -= $weeks * 604800;
    }

    $days = floor ($seconds / 86400);

    if (count($str) < $units && ($months > 0 || $weeks > 0 || $days > 0)) {
      if ($days > 0) array_push ($str, $days . ' 天');
      $seconds -= $days * 86400;
    }

    $hours = floor ($seconds / 3600);

    if (count($str) < $units && ($days > 0 || $hours > 0)) {
      if ($hours > 0) array_push ($str, $hours . ' 小時');
    }

    $minutes = floor ($seconds / 60);

    if (count ($str) < $units && ($days > 0 || $hours > 0 || $minutes > 0)) {
      if ($minutes > 0) array_push ($str,  $minutes . ' 分');
      $seconds -= $minutes * 60;
    }

    if (count ($str) === 0)
      $str[] = $seconds . ' 秒';

    return implode (', ', $str);
  }
}

if (!function_exists ('days_in_month')) {
  function days_in_month ($month = 0, $year = '') {
    if ($month < 1 || $month > 12)
      return 0;
    
    if (!is_numeric ($year) || strlen ($year) !== 4)
      $year = date ('Y');

    if (defined ('CAL_GREGORIAN'))
      return cal_days_in_month (CAL_GREGORIAN, $month, $year);

    if ($year >= 1970)
      return (int)date ('t', mktime (12, 0, 0, $month, 1, $year));

    if ($month == 2)
      if ($year % 400 === 0 || ($year % 4 === 0 && $year % 100 !== 0))
        return 29;

    $t = array (31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    return $t[$month - 1];
  }
}

if (!function_exists ('local_to_gmt')) {
  function local_to_gmt ($time = '') {
    $time === '' && $time = time ();
    return mktime (gmdate ('G', $time), gmdate ('i', $time), gmdate ('s', $time), gmdate ('n', $time), gmdate ('j', $time), gmdate ('Y', $time));
  }
}

if (!function_exists ('gmt_to_local')) {
  function gmt_to_local ($time = '', $timezone = 'UTC', $dst = false) {
    $time === '' && now ();
    $time += timezones ($timezone) * 3600;
    return ($dst === true) ? $time + 3600 : $time;
  }
}

if (!function_exists ('mysql_to_unix')) {
  function mysql_to_unix ($time = '') {
    $time = str_replace (array ('-', ':', ' '), '', $time);
    return mktime (substr ($time, 8, 2), substr ($time, 10, 2), substr ($time, 12, 2), substr ($time, 4, 2), substr ($time, 6, 2), substr ($time, 0, 4));
  }
}

if (!function_exists ('unix_to_human')) {
  function unix_to_human ($time = '', $seconds = false, $fmt = 'us') {
    $r = date ('Y', $time) . '-' . date ('m', $time) . '-' . date ('d', $time) . ' ';

    $r .= ($fmt === 'us' ? date ('h', $time) . ':' . date ('i', $time) : date ('H', $time) . ':' . date ('i', $time));

    if ($seconds)
      $r .= ':' . date ('s', $time);

    return $fmt === 'us' ? $r . ' ' . date ('A', $time) : $r;
  }
}

if (!function_exists ('human_to_unix')) {
  function human_to_unix ($datestr = '') {
    if (!$datestr)
      return false;

    $datestr = preg_replace ('/\040+/', ' ', trim ($datestr));

    if (!preg_match ('/^(\d{2}|\d{4})\-[0-9]{1,2}\-[0-9]{1,2}\s[0-9]{1,2}:[0-9]{1,2}(?::[0-9]{1,2})?(?:\s[AP]M)?$/i', $datestr))
      return false;

    sscanf ($datestr, '%d-%d-%d %s %s', $year, $month, $day, $time, $ampm);
    sscanf ($time, '%d:%d:%d', $hour, $min, $sec);
    isset ($sec) || $sec = 0;

    if (isset ($ampm)) {
      $ampm = strtolower ($ampm);

      if ($ampm[0] === 'p' && $hour < 12)
        $hour += 12;
      else if ($ampm[0] === 'a' && $hour === 12)
        $hour = 0;
    }

    return mktime ($hour, $min, $sec, $month, $day, $year);
  }
}

if (!function_exists ('nice_date')) {
  function nice_date ($bad_date = '', $format = false) {
    if (!$bad_date)
      return 'Unknown';

    $format || $format = 'U';

    if (preg_match ('/^\d{6}$/i', $bad_date)) {
      if (in_array (substr ($bad_date, 0, 2), array ('19', '20'))) {
        $year  = substr ($bad_date, 0, 4);
        $month = substr ($bad_date, 4, 2);
      } else {
        $month  = substr ($bad_date, 0, 2);
        $year   = substr ($bad_date, 2, 4);
      }

      return date ($format, strtotime ($year . '-' . $month . '-01'));
    }

    if (preg_match ('/^\d{8}$/i', $bad_date, $matches))
      return DateTime::createFromFormat ('Ymd', $bad_date)->format ($format);

    if (preg_match ('/^(\d{1,2})-(\d{1,2})-(\d{4})$/i', $bad_date, $matches))
      return date ($format, strtotime ($matches[3] . '-' . $matches[1] . '-' . $matches[2]));

    return date ('U', strtotime ($bad_date)) === '0' ? 'Invalid Date' : date ($format, strtotime ($bad_date));
  }
}

if (!function_exists ('timezone_menu')) {
  function timezone_menu ($default = 'UTC', $class = '', $name = 'timezones', $attributes = '') {
    $default = $default === 'GMT' ? 'UTC' : $default;
    $menu = '<select name="' . $name . '"';
    $class !== '' && ($menu .= ' class="' . $class . '"');
    $menu .= stringify_attributes ($attributes) . ">\n";

    $zones = config ('timezones');

    foreach ($zones as $key => $val)
      $menu .= '<option value="' . $key . '"' . ($default === $key ? ' selected="selected"' : '') . '>' . $val[1] . "</option>\n";

    return $menu . '</select>';
  }
}

if (!function_exists ('timezones')) {
  function timezones ($tz = '') {
    $zones = config ('timezones');
    return $tz !== '' ? isset ($zones[$tz]) ? $zones[$tz][0] : 0 : array_column ($zones, 0);
  }
}

if (!function_exists ('date_range')) {
  function date_range ($unix_start = '', $mixed = '', $is_unix = true, $format = 'Y-m-d') {
    if ($unix_start == '' || $mixed == '' || $format == '')
      return false;

    $is_unix = !(!$is_unix || $is_unix === 'days');

    if ((!ctype_digit ((string) $unix_start) && ($unix_start = @strtotime ($unix_start)) === false) || (!ctype_digit ((string) $mixed) && ($is_unix === false || ($mixed = @strtotime ($mixed)) === false)) || ($is_unix === true && $mixed < $unix_start))
      return false;

    if ($is_unix && ($unix_start == $mixed || date ($format, $unix_start) === date ($format, $mixed)))
      return array (date ($format, $unix_start));

    if ($is_unix) {
      $arg = new DateTime ();
      $arg->setTimestamp ($mixed);
    } else {
      $arg = (int)$mixed;
    }

    $from = new DateTime ();
    $from->setTimestamp ($unix_start);

    $range = array ();
    foreach (new DatePeriod ($from, new DateInterval ('P1D'), $arg) as $date)
      array_push ($range, $date->format ($format));

    if (!is_int ($arg) && $range[count ($range) - 1] !== $arg->format ($format))
      array_push ($range, $arg->format ($format));

    return $range;
  }
}
