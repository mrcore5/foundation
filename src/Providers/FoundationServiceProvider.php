<?php namespace Mrcore\Foundation\Providers;

#use Gate;
use Event;
use Layout;
use Module;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class FoundationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

        /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register facades and class aliases
        $this->registerFacades();

        // Mrcore Module Tracking
        // This below facaces only in Foudation becuase
        // this is where Module:: facade comes from
        Module::trace(get_class(), __function__);

        // Register configs
        $this->registerConfigs();

        // Register services
        $this->registerServices();

        // Register testing environment
        #$this->registerTestingEnvironment();

        // Register mrcore modules
        $this->registerModules();
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Kernel $kernel, Router $router, Request $request)
    {
        // Mrcore Module Tracking
        Module::trace(get_class(), __function__);

        // Register publishers
        $this->registerPublishers();

        // Load our custom macros
        require __DIR__.'/../Support/Macros.php';

        // Register migrations
        #$this->registerMigrations();

        // Register Policies
        #$this->registerPolicies();

        // Register global and route based middleware
        $this->registerMiddleware($kernel, $router);

        // Register event listeners and subscriptions
        #$this->registerListeners();

        // Register scheduled tasks
        #$this->registerSchedules();

        // Register mrcore layout overrides
        $this->registerLayout($request);

        if ($this->app->runningInConsole()) {
            // We are running in the console (artisan, testing or queue worders)
            // The console does NOT use HTTP middleware which is where my
            // Module system calls loadViews() and loadRoutes().  We don't really
            // need routes for console apps, but we DO need the views registered
            // in case any console app or worker needs access to module views like
            // for sending emails.  So do it here only if console, else it will
            // load as usual in Foundation/Http/Middleware/LoadModules.php!
            Module::loadViews();
        }

        // Register artisan commands
        $this->registerCommands();
    }

    /**
     * Register facades and class aliases.
     *
     * @return void
     */
    protected function registerFacades()
    {
        $facade = AliasLoader::getInstance();
        $facade->alias('Module', 'Mrcore\Foundation\Facades\Module');
        $facade->alias('Layout', 'Mrcore\Foundation\Facades\Layout');
        #class_alias('Some\Long\Class', 'Short');

        // Backward compatibility
        $facade->alias('Form', \Collective\Html\FormFacade::class);
        $facade->alias('Html', \Collective\Html\HtmlFacade::class);
        $facade->alias('Input', \Illuminate\Support\Facades\Input::class);
    }

    /**
     * Register additional configs and merges.
     *
     * @return void
     */
    protected function registerConfigs()
    {
        // Append or overwrite configs
        #config(['test' => 'hi']);

        // Merge configs
        $this->mergeConfigFrom(__DIR__.'/../../config/foundation.php', 'mrcore.foundation');
    }

    /**
     * Register the application and other services.
     *
     * @return void
     */
    protected function registerServices()
    {
        // Register IoC bind aliases and singletons
        #$this->app->alias(\Mrcore\Appstub\Appstub::class, \Mrcore\Appstub::class)
        #$this->app->singleton(\Mrcore\Appstub\Appstub::class, \Mrcore\Appstub::class)

        // Register UrlServiceProvider (laravel override) for mreschke https ssl termination fix
        // FIXME in the fiture, fideloper fixed this, see https://github.com/fideloper/TrustedProxy
        $this->app->register(\Mrcore\Foundation\Providers\UrlServiceProvider::class);

        // Register other service providers
        $this->app->register(\Collective\Html\HtmlServiceProvider::class);
    }

    /**
     * Register test environment overrides
     *
     * @return void
     */
    public function registerTestingEnvironment()
    {
        // Register testing environment
        if ($this->app->environment('testing')) {
            //
        }
    }

    /**
     * Register mrcore modules
     *
     * @return void
     */
    public function registerModules()
    {
        // Initialize modules config
        Module::loadConfig(config('modules'));

        // Configure Layout (this must be here, not in boot, not in middleware)
        Module::configureThemes();

        // Register all enabled mrcore modules
        Module::register();
    }

    /**
     * Define the published resources and configs.
     *
     * @return void
     */
    protected function registerPublishers()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // Register additional css assets with mrcore Layout
        //Layout::css('css/wiki-bundle.css');

        // App base path
        $path = realpath(__DIR__.'/../../');

        // Config publishing rules
        // ./artisan vendor:publish --tag="mrcore.foundation.configs"
        $this->publishes([
            "$path/config/foundation.php" => base_path('config/mrcore/foundation.php'),
        ], 'mrcore.foundation.configs');

        // ./artisan vendor:publish --tag="mrcore.modules.configs"
        $this->publishes([
            "$path/config/modules.php" => base_path('config/modules.php'),
        ], 'mrcore.modules.configs');

        /*// Migration publishing rules
        // ./artisan vendor:publish --tag="mrcore.appstub.migrations"
        $this->publishes([
            "$path/database/migrations" => base_path('/database/migrations'),
        ], 'mrcore.appstub.migrations');

        // Seed publishing rules
        // ./artisan vendor:publish --tag="mrcore.appstub.seeds"
        $this->publishes([
            "$path/database/seeds" => base_path('/database/seeds'),
        ], 'mrcore.appstub.seeds');*/
    }

    /**
     * Register the migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        // Only applies if running in console
        if (!$this->app->runningInConsole()) return;

        // Register Migrations
        #$this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    /**
     * Register permission policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        // Define permissions (closure or Class@method)
        #Gate::define('update-post', function($user, $post) {
        #    return $user->id === $post->user_id;
        #});

        #Gate::before(function ($user, $ability) {
        #    if ($user->isSuperAdmin()) {
        #        return true;
        #    }
        #});
        # ->after() is also available

        // Or define an entire policy class
        #Gate::policy(\App\Post::class, \App\Policies\PostPolicy::class);
    }

    /**
     * Register global and route based middleware.
     *
     * @param Illuminate\Contracts\Http\Kernel $kernel
     * @param \Illuminate\Routing\Router $router
     * @return  void
     */
    protected function registerMiddleware(Kernel $kernel, Router $router)
    {
        // Does not apply if running in console
        if ($this->app->runningInConsole()) return;

        // Register global middleware
        $kernel->pushMiddleware(\Mrcore\Foundation\Http\Middleware\LoadModules::class);

        // Register route based middleware
        #$router->middleware('auth.appstub', 'Mrcore\Appstub\Http\Middleware\Authenticate');
    }

    /**
     * Register event listeners and subscriptions.
     *
     * @return void
     */
    protected function registerListeners()
    {
        // Login event listener
        #Event::listen('Illuminate\Auth\Events\Login', function($user) {
            //
        #});

        // Logout event subscriber
        #Event::subscribe('Mrcore\Appstub\Listeners\MyEventSubscription');
    }

    /**
     * Register the scheduled tasks
     *
     * @return void
     */
    protected function registerSchedules()
    {
        // Register all task schedules for this hostname ONLY if running from the schedule:run command
        /*if (app()->runningInConsole() && isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'schedule:run') {

            // Defer until after all providers booted, or the scheduler instance is removed from Illuminate\Foundation\Console\Kernel defineConsoleSchedule()
            $this->app->booted(function() {

                // Get the scheduler instance
                $schedule = app('Illuminate\Console\Scheduling\Schedule');

                // Define our schedules
                $schedule->call(function() {
                    echo "hi";
                })->everyMinute();

            });
        }*/
    }

    /**
     * Register mrcore layout overrides.
     *
     * @return void
     */
    protected function registerLayout($request)
    {
        // Does not apply if running in console
        if ($this->app->runningInConsole()) return;

        // Configure layout modes
        $simpleMode = $request->input('simple');
        if (isset($simpleMode) || $request->input('viewmode') == 'simple') {
            Layout::mode('simple');
        }
        $rawMode = $request->input('raw');
        if (isset($rawMode) || $request->input('viewmode') == 'raw') {
            Layout::mode('raw');
        }
        $defaultMode = $request->input('default');
        if (isset($defaultMode) || $request->input('viewmode') == 'default') {
            Layout::mode('default');
        }

        // Register additional css assets with mrcore Layout
        #Layout::css('css/wiki-bundle.css');
    }

        /**
     * Register artisan commands.
     * @return void
     */
    protected function registerCommands()
    {
        // Only applies if running in console
        if (!$this->app->runningInConsole()) return;

        // Register Commands
        $this->commands([
            \Mrcore\Foundation\Console\Commands\ClearQueueCommand::class,
            \Mrcore\Foundation\Console\Commands\InstallCommand::class,
            \Mrcore\Foundation\Console\Commands\AppMakeCommand::class,
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        // Only required if $defer = true and you add bindings
        //return ['Mrcore\Appstub\Stuff', 'other bindings...'];
    }
}
