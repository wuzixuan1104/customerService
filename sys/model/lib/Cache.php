<?php
namespace ActiveRecord;
use Closure;

class Cache {
	static $adapter = null;
	static $expire = 30;
	static $prefix = '';

	public static function initialize ($driver, $prefix = null, $expire = null) {
		if (!class_exists ('\Cache'))
			return static::$adapter = null;

		static::$adapter = \Cache::initialize ($driver, array (
				'prefix' => $prefix ? $prefix : static::$prefix,
				'expire' => $expire ? $expire : static::$expire
			));
	}

	public static function flush () {
		static::$adapter || static::$adapter->clean ();
	}

	public static function get ($key, $closure, $expire = null) {
		if (!static::$adapter)
			return $closure();

		if (($value = static::$adapter->get ($key = static::getNamespace () . $key)) === null)
			static::$adapter->save ($key, $value = $closure (), $expire ? $expire : static::$expire);

		return $value;
	}

	public static function set ($key, $var, $expire = null) {
		return static::$adapter ? static::$adapter->save (static::getNamespace () . $key, $var, $expire ? $expire : static::$expire) : null;
	}

	public static function delete($key) {
		return static::$adapter ? static::$adapter->delete (static::getNamespace () . $key) : null;
	}

	private static function getNamespace () {
		return self::$prefix;
	}
}
