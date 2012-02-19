<?php defined('SYSPATH') or die('No direct script access.');

class Urlconf_Route extends Kohana_Route {

	const DEFAUlT_URLCONF = 'default';

	protected static $_current_urlconf = Urlconf_Route::DEFAUlT_URLCONF;

	protected static $_confed_routes = array();

	protected static $_set_lock = TRUE;

	public static function set($name, $uri_callback = NULL, $regex = NULL)
	{
		if (self::$_set_lock)
		{
			throw new Kohana_Exception("All rotes must be defined in urls folder"
				+ " and will be imported automatically by Route module.");
		}

		$route = new Route($uri_callback, $regex);

		return self::$_confed_routes[self::$_current_urlconf][$name] = $route;
	}

	public static function get($name, $urlconf = NULL)
	{
		if ($urlconf === NULL)
		{
			$urlconf = self::$_current_urlconf;
		}

		// Load urlconf
		self::load_urlconf($urlconf);

		if ( ! isset(self::$_confed_routes[$urlconf][$name]))
		{
			throw new Kohana_Exception('The requested route does not exist: :route',
				array(':route' => $name));
		}

		return self::$_confed_routes[$urlconf][$name];
	}

	public static function all($urlconf = NULL)
	{
		if ($urlconf === NULL)
		{
			$urlconf = self::$_current_urlconf;
		}

		// Load urlconf
		self::load_urlconf($urlconf);

		return self::$_confed_routes[$urlconf];
	}

	public static function name(Route $route, $urlconf = NULL)
	{
		if ($urlconf === NULL)
		{
			$urlconf = self::$_current_urlconf;
		}

		// if urlconfig not loaded or empty it always FALSE
		if (empty(self::$_confed_routes[$urlconf]))
		{
			return FALSE;
		}

		return array_search($route, self::$_confed_routes[$urlconf]);
	}

	public static function current_urlconf($urlconf = NULL)
	{
		if ($urlconf === NULL) {
			return self::$_current_urlconf;
		}
		else
		{
			self::$_current_urlconf = $urlconf;
		}
	}

	protected static function load_urlconf($urlconf)
	{
		// Already loaded
		if (isset(self::$_confed_routes[$urlconf]))
		{
			return;
		}

		if (Kohana::$caching === TRUE)
		{
			$cache_key = "Urlconf_Route::load_urlconf(".$urlconf.")";

			// Try load routes from cache
			self::$_confed_routes[$urlconf] = Kohana::cache($cache_key);

			// Routes were in cache
			if (is_array(self::$_confed_routes[$urlconf]))
			{
				return;
			}
		}

		// Save provios value of current urlconf
		$save_urlconf = self::$_current_urlconf;

		// Save lock state to allow nested loads
		$save_lock = self::$_set_lock;

		// Set required urlconf as cuurent
		self::$_current_urlconf = $urlconf;

		self::$_set_lock = FALSE;

		self::$_confed_routes[$urlconf] = array();

		if ($file = Kohana::find_file('urls', $urlconf))
		{
			require $file;
		}
		else
		{
			throw new Kohana_Exception("Can't load urlconf: :urlconf",
				array(':urlconf' => $urlconf));
		}

		// Restore previous urlconf
		self::$_current_urlconf = $save_urlconf;

		// Restore lock
		self::$_set_lock = $save_lock;

		if (Kohana::$caching === TRUE)
		{
			Kohana::cache($cache_key, self::$_confed_routes[$urlconf]);
		}
	}

	public static function cache($save = FALSE)
	{
		// Avoid routes built-in cache
		throw new Kohana_Exception("You should not use routes built-in cache. All routes cashed in case of Kohana::caching.");
	}
}
