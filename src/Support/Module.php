<?php namespace Mrcore\Foundation\Support;

use App;
use URL;
use View;
use Config;
use Route;
use Layout;

// CAREFUL: This class is instantiated in the asset manager
// which is loaded BEFORE laravel.  So all laravel function are NOT
// available to this class if used by the asset manager

class Module
{
    protected $config;

    public function __construct()
    {
        //
    }

    /**
     * Load modules configuration from main config/modules.php and each packages config
     * @param  array $config config/modules.php modules configuration
     * @return void
     */
    public function loadConfig($config)
    {
        // Dependencies
        $this->config = $config;

        if (!isset($config['modules'])) return;

        // Loop each config and merge configs
        foreach ($config['modules'] as $name => $module) {
            // Merge modules config with defaults and packages config override
            $module = $this->mergeConfig($name, $module);

            // Write back to config for other function usage
            $this->config['modules'][$name] = $module;

            // If disabled, remove from config
            if ($module['enabled'] === false) unset($this->config['modules'][$name]);
        }
    }

    /**
     * // Merge modules config with defaults and packages config override
     * @param  string $name
     * @param  array $module
     * @return array
     */
    public function mergeConfig($name, $module)
    {
        // Parse vendor/package
        list($psrVendor, $psrPackage) = explode('\\', $name);
        $vendor = kebab_case($psrVendor);
        $package = kebab_case($psrPackage);

        // Merge with defaults
        $module = array_merge([
            // Types include module, foundation, basetheme, subtheme, app
            'type' => 'module',

            // Name must be PSR namespace (VendorName\PackageName)
            'name' => $name,

            // Vendor Package
            'vendor' => $vendor,
            'package' => $package,
            'psrVendor' => $psrVendor,
            'psrPackage' => $psrPackage,

            // Base path to package, use vendor directory for ultimate consistency
            // Can be array of multiple paths, first wins
            'path' => "vendor/$vendor/$package",

            // Namespace defaults to name since name should be PSR namespace
            'namespace' => $name,

            // Controller namespace always in Http\Controllers
            'controller_namespace' => "$name\Http\Controllers",

            // Service Provider always in Providers\PackageServiceProvider
            // Null means no provider to load
            'provider' => "$name\Providers\\".$psrPackage."ServiceProvider",

            // Routes is an optional path to routes php file. If routes=null do not load routes file or prefix
            'routes' => null,
            'route_prefix' => null,

            // Views is an optional path to views folder.  If views=null do not load views or prefix
            // Can be array of multiple paths, all paths are registered as valid view paths
            'views' => null,
            'view_prefix' => null,

            // Single path to assets folder, used in asset manager, not in this Module.php script
            'assets' => null,

            // Only register this module if App::runningInConsole(), not on the web
            'console_only' => null,

            // Enable this module
            'enabled' => true,
        ], $module);

        // Write back to config for other function usage
        $this->config['modules'][$name] = $module;

        // Merge in packages config 'paths' array.   Ensure global config/modules.php wins over packages config
        $packageConfig = $this->getPackageConfig($name, $module);
        if (isset($packageConfig) && isset($packageConfig['paths'])) {
            $paths = $packageConfig['paths'];
            if (!isset($module['routes'])) $module['routes'] = $paths['routes'];
            if (!isset($module['route_prefix'])) $module['route_prefix'] = $paths['route_prefix'];
            if (!isset($module['views'])) $module['views'] = $paths['views'];
            if (!isset($module['view_prefix'])) $module['view_prefix'] = $paths['view_prefix'];
            if (!isset($module['assets'])) $module['assets'] = $paths['public']; // Yes public variable
        }

        // Write back to config for other function usage
        $this->config['modules'][$name] = $module;

        // Return
        return $module;
    }

    /**
     * [Merge in packages config 'paths' array.   Ensure global config/modules.php wins over packages config
     * @param  array $module
     * @return array
     */
    protected function getPackageConfig($name, $module)
    {
        // Merge in packages config 'paths' array.   Ensure global config/modules.php wins over packages config
        $path = $this->getPath($name);
        $package = $module['package'];
        if (file_exists("$path/config/$package.php")) {
            return include("$path/config/$package.php");
        } elseif (file_exists("$path/Config/$package.php")) {
            return include("$path/Config/$package.php");
        }
    }

    /**
     * Get the module marked as type=app for %app% merge
     * @return array
     */
    protected function getAppModule()
    {
        if ($modules = $this->all()) {
            foreach ($modules as $module) {
                if ($module['type'] == 'app') {
                    return $module;
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
        // Find dynamic %app% module
        if ($name == '%app%') return $this->getAppModule();

        // Find regular module
        if ($this->exists($name)) return $this->config['modules'][$name];
    }

    /**
     * Check if a module exists
     * @param  string $name
     * @return boolean
     */
    public function exists($name)
    {
        return isset($this->config['modules'][$name]);
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
        if (isset($module) && isset($module[$key])) {
            return $module[$key];
        }
    }

    /**
     * Get all enabled modules
     * @return array
     */
    public function all()
    {
        return $this->config['modules'] ?: [];
    }

    /**
     * Add module to the modules array (dynamic at run-time) and register it!
     * @param array $module
     */
    public function addModule($name, $module = [], $registerModule = true)
    {
        // We do allow adding even if exists.  Adding will override whats defined in modules config
        // Merge modules config with defaults and packages config override
        $module = $this->mergeConfig($name, $module);

        // Write back to config for other function usage
        $this->config['modules'][$name] = $module;

        // Register new dynamically added module!
        if ($registerModule) $this->register($name);
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
            if (!isset($module)) return;
            $consoleOnly = $module['console_only'];
            if ($force || !$consoleOnly || ($consoleOnly && App::runningInConsole())) {

                // Load autoloader first so I can find the service provider namespace
                // Only loaded if vendor/autoload.php exists.  Autoloaders are optional and generally not used in each module.
                $this->loadAutoloaders($module['name']);

                if ($module['type'] != 'foundation') {
                    // Register this modules service provider
                    if (isset($module['provider'])) App::register($module['provider']);
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
     * Load autoloader files from one or all modules only if vendor/autuoload.php exists
     * @param  string $name = null
     * @return void
     */
    public function loadAutoloaders($name = null)
    {
        if (isset($name)) {
            // Load autoloader from a single module only if vendor/autoload.php exists
            $module = $this->find($name);
            if (!isset($module)) return;
            if ($path = realpath($this->getPath($name).'/vendor/autoload.php')) {
                // Load this modules autoload.php file
                $this->trace($path);
                require $path;
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
            if (!isset($module)) return;
            if (isset($module['views'])) {
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
            if ($views = $this->config['views']) {
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
            if (!isset($module)) return;
            if (isset($module['routes'])) {
                if ($path = $this->getPath($name, 'routes')) {
                    // Load this modules routes
                    $this->trace($path);
                    $prefix = (isset($module['route_prefix']) ? $module['route_prefix'] : '');
                    Route::group([
                        'namespace' => $module['controller_namespace'],
                        'prefix' => $prefix,
                        'middleware' => 'web',
                    ], function ($router) use ($path) {
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
            if ($routes = $this->config['routes']) {
                // Routes are reversed, last one wins because its a require statement
                $routes = array_reverse($routes);
                foreach ($routes as $route) {
                    $this->loadRoutes($route); //recursion
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
        // Get theme modules css for both base and sub themes
        $modules = $this->all();
        $baseThemeCss = $subThemeCss = null;
        foreach ($modules as $module) {
            if ($module['type'] == 'basetheme') {
                $baseThemeCss = $module['css'];
            } elseif ($module['type'] == 'subtheme') {
                $subThemeCss = $module['css'];
            }
        }

        // Get theme container settings
        $container = $this->themeContainers();

        // Add theme css
        $css = $baseThemeCss;
        if (isset($subThemeCss)) {
            $css = $subThemeCss;
        } //subtheme wins
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
     * Get the container booleans
     * @return array
     */
    public function themeContainers()
    {
        // Get theme container settings if exists.  If not, set defaults
        $baseThemeCss = $subThemeCss = null;
        $modules = $this->all();
        $container = [];
        foreach ($modules as $module) {
            if ($module['type'] == 'basetheme') {
                $container = $module['container'];
            }
        }
        $container = array_merge([
            'header' => true,
            'body' => true,
            'footer' => true,
        ], $container);
        return $container;
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
            if ($this->config['debug']) {
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
    public function getPath($name, $item = null)
    {
        $module = $this->find($name);
        if (!isset($module)) return;
        $paths = is_array($module['path']) ? $module['path'] : [$module['path']];

        // Path can be an array of paths (../Apps, ../vendor...) first one found wins
        foreach ($paths as $path) {
            if (substr($path, 0, 1) != '/') {
                $path = base_path($path);
            } // relatove to absolute
            if ($path = realpath($path)) {
                break;
            }
        }

        // Append module item to path
        if ($path && isset($item)) {
            $path = realpath($path."/".$module[$item]);
        }
        return $path;
    }

    /**
     * Resolve real paths for an item in proper order
     * @param  string $item
     * @return array
     */
    private function getPaths($item)
    {
        $paths = array();
        foreach ($this->config[$item] as $moduleName) {
            $module = $this->find($moduleName);
            if (!isset($module)) continue;
            if (isset($module[$item])) {
                if ($path = $this->getPath($moduleName, $item)) {
                    $paths[] = $path;
                }
            }
        }
        return $paths;
    }

}
