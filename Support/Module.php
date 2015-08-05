<?php namespace Mrcore\Modules\Foundation\Support;

use App;
use URL;
use View;
use Config;
use Route;
use Layout;

class Module {

	protected $modules;

	public function __construct() {
		// Add only enabled modules
		$this->modules = array();
		$allModules = Config::get('modules.modules');
		if (isset($allModules)) {
			foreach ($allModules as $name => $module) {
				if (!isset($module['enabled']) || $module['enabled'] == true) {
					$module['name'] = $name;
					$this->modules[$name] = $module;
				}
			}
		}
	}

	/**
	 * Get an enabled module
	 * @param  string $name
	 * @return array
	 */
	public function find($name)
	{
		if (isset($this->modules[$name])) {
			return $this->modules[$name];
		}
	}

	/**
	 * Get a key value from a module
	 * @param  string $name module name
	 * @param  string $key module array key
	 * @return mixed
	 */
	public function get($name, $key)
	{
		$module = $this->find($name);
		if (isset($module)) {
			if (isset($module[$key])) {
				return $module[$key];
			}
		}
	}

	/**
	 * Get all enabled modules
	 * @return array
	 */
	public function all()
	{
		return $this->modules;
	}

	/**
	 * Add module to the modules array (dynamic at run-time) and register it!
	 * @param array $module
	 */
	public function addModule($name, $module)
	{
		// Add to array
		$this->modules[$name] = $module;
		$this->modules[$name]['name'] = $name;

		// Register new dynamically added module!
		$this->register($name);
	}

	/**
	 * Get all modules assets, full path, in proper order
	 * @return array
	 */
	public function assets()
	{
		return $this->getPaths('assets');
	}

	/**
	 * Get all modules views, full path, in proper order
	 * @return array
	 */
	public function views()
	{
		return $this->getPaths('views');
	}

	/**
	 * Get all modules routes, full path, in proper order
	 * @return array
	 */
	public function routes()
	{
		return $this->getPaths('routes');
	}

	/**
	 * Register one or all modules service provider
	 * @param  string $name = null
	 * @param  boolean $force will load module even if console_only
	 * @return void
	 */
	public function register($name = null, $force = false)
	{
		if (isset($name)) {
			// Register a single module
			$module = $this->find($name);

			$consoleOnly = isset($module['console_only']) ? $module['console_only'] : false;
			if ($force || !$consoleOnly || ($consoleOnly && App::runningInConsole())) {
				// Load autoloader first so I can find the service provider namespace
				$this->loadAutoloaders($module['name']);

				if (isset($module)) {
					if ($module['type'] != 'foundation') {
						// Register this modules service provider
						if (isset($module['provider'])) {
							App::register($module['provider']);
						}
					}
				}
			}
		} else {
			// Register all modules
			if ($modules = $this->all()) {
				foreach ($modules as $module) {
					$this->register($module['name']); //recursion
				}
			}
		}
	}

	/**
	 * Load autoloader files from one or all modules
	 * @param  string $name = null
	 * @return void
	 */
	public function loadAutoloaders($name = null)
	{
		if (isset($name)) {
			// Load autoloader from a single module
			$module = $this->find($name);
			if (isset($module)) {
				if ($path = realpath($this->getPath($name).'/vendor/autoload.php')) {
					// Load this modules autoload.php file
					$this->trace($path);
					require $path;
				}
			}
		} else {
			// Load autoloader from all modules
			if ($modules = $this->all()) {
				foreach ($modules as $module) {
					$this->loadAutoloaders($module['name']); //recursion
				}
			}
		}
	}

	/**
	 * Load view files from one or all modules, in proper order
	 * @param  string $name = null
	 * @return void
	 */
	public function loadViews($name = null)
	{
		if (isset($name)) {
			// Load views from a single module
			$module = $this->find($name);
			if (isset($module) && isset($module['views'])) {
				if ($path = $this->getPath($module['name'], 'views')) {
					// Load this modules views
					$this->trace($path);
					if (isset($module['view_prefix'])) {
						View::addNamespace($module['view_prefix'], $path);
					} else {
						View::addLocation($path);
					}
				}
			}
		} else {
			// Load views from all modules, in proper order
			if ($views = Config::get("modules.views")) {
				foreach ($views as $view) {
					$this->loadViews($view); //recursion
				}
			}
		}
	}

	/**
	 * Load routes files from one or all modules, in proper order
	 * @param  string $name = null
	 * @return void
	 */
	public function loadRoutes($name = null)
	{
		if (isset($name)) {
			// Load routes from a single module
			$module = $this->find($name);
			if (isset($module) && isset($module['routes'])) {
				if ($path = $this->getPath($name, 'routes')) {
					// Load this modules routes
					$this->trace($path);
					$prefix = (isset($module['route_prefix']) ? $module['route_prefix'] : '');
					Route::group(['namespace' => $module['controller_namespace'], 'prefix' => $prefix], function($router) use($path) {
						require $path;
					});

					// Register the root controller namespace with the URL generator only for %apps%
					if ($module['type'] == "app") {
						URL::setRootControllerNamespace($module['controller_namespace']);
					}
				}
			}
		} else {
			// Load routes from all modules
			if ($routes = Config::get("modules.routes")) {
				// Routes are reversed, last one wins because its a require statement
				$routes = array_reverse($routes);
				foreach ($routes as $moduleName) {
					$this->loadRoutes($moduleName); //recursion
				}
			}
		}
	}

	/**
	 * Configure themes (css, container)
	 * @return void
	 */
	public function configureThemes()
	{
		// Get theme module css and bootstrap container configurations
		$baseThemeCss = $subThemeCss = null;
		$modules = $this->all();
		foreach ($modules as $module) {
			if ($module['type'] == 'basetheme') {
				$baseThemeCss = $module['css'];
				$container = $module['container'];

			} elseif ($module['type'] == 'subtheme') {
				$subThemeCss = $module['css'];

			}
		}

		// Add theme css
		$css = $baseThemeCss;
		if (isset($subThemeCss)) $css = $subThemeCss; //subtheme wins
		if (isset($css)) {
			foreach ($css as $style) {
				Layout::css($style);
			}
		}

		// Set theme bootstrap containers
		if (isset($container)) {
			Layout::container(
				$container['body'], $container['header'], $container['footer']
			);
		}
	}

	/**
	 * Get/set module tracking trace
	 * @param  string $class
	 * @param  string $function
	 * @return mixed
	 */
	public function trace($class = null, $function = null)
	{
		if (isset($class)) {
			// Add to module dump
			if (Config::get('modules.debug')) {

				if (!isset($this->app['modules.dump'])) {
					$this->app['modules.dump'] = array();
				}
				$dump = $this->app['modules.dump'];
				if (isset($function)) {
					$dump[] = "$class\\${function}()";
				} else {
					$dump[] = "$class";
				}

				$this->app['modules.dump'] = $dump;
			}

		} else {
			return $this->app['modules.dump'];
		}
	}

	/**
	 * Resolve real path for a single module and item
	 * @param  string $name
	 * @param  string $item = null
	 * @return string|boolean
	 */
	private function getPath($name, $item = null)
	{
		$module = $this->find($name);
		if (isset($module)) {
			$paths = is_array($module['path']) ? $module['path'] : [$module['path']];

			// Path can be an array of paths (../Apps, ../vendor...) first one found wins
			foreach ($paths as $path) {
				if (substr($path, 0, 1) != '/') $path = base_path($path); // relatove to absolute
				if ($path = realpath($path)) {
					break;
				}
			}

			// Append module item to path
			if ($path && isset($item)) $path = realpath($path."/".$module[$item]);
			return $path;
		}
	}

	/**
	 * Resolve real paths for an item in proper order
	 * @param  string $item
	 * @return array
	 */
	private function getPaths($item)
	{
		$paths = array();
		foreach (Config::get("modules.$item") as $moduleName) {
			$module = $this->find($moduleName);
			if (isset($module)) {
				if (isset($module[$item])) {
					if ($path = $this->getPath($moduleName, $item)) {
						$paths[] = $path;
					}
				}
			}
		}
		return $paths;
	}

}