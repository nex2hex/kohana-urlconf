<?php defined('SYSPATH') or die('No direct script access.');

class Urlconf_Route extends Kohana_Route {

	const DEFAUlT_URLCONF = 'default';

	protected static $_current_urlconf = Urlconf_Route::DEFAUlT_URLCONF;

	protected static $_confed_routes = array();

	/**
	 * Retrieves named routes for current urlconf. Autocached.
	 *
	 *     $routes = Route::all();
	 *
	 * @return  array  routes by name
	 */
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

	public static function set($name, $uri_callback = NULL, $regex = NULL)
	{
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

	public static function load_urlconf($urlconf)
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
			if (self::$_confed_routes[$urlconf] !== NULL)
			{
				return;
			}
		}

		// Save provios value of current urlconf
		$save_urlconf = self::$_current_urlconf;

		// Set required urlconf as cuurent
		self::$_current_urlconf = $urlconf;

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

		// Restore previous
		self::$_current_urlconf = $save_urlconf;

		if (Kohana::$caching === TRUE)
		{
			Kohana::cache($cache_key, self::$_confed_routes[$urlconf]);
		}
	}

	public static function cache($save = FALSE)
	{
		// Avoid routes built-in cache
		return FALSE;
	}
}
