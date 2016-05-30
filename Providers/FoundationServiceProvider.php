<?php namespace Mrcore\Foundation\Providers;

use View;
use Config;
use Layout;
use Module;
use Illuminate\Http\Request;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class FoundationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application services.
	 * @param  Illuminate\Routing\Router $router
	 *
	 * @return void
	 */
	public function boot(Request $request)
	{
		// Mrcore Module Tracking
		Module::trace(get_class(), __function__);

		// Define publishing rules
		$this->definePublishing();

		// Load our custom macros
		require __DIR__.'/../Support/Macros.php';

		// Register Middleware (in boot() to be last, not register())
		$kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');
		$kernel->pushMiddleware('Mrcore\Foundation\Http\Middleware\LoadModules');

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

		if (app()->runningInConsole()) {
			// We are running in the console (artisan, testing or queue worders)
			// The console does NOT use HTTP middleware which is where my
			// Module system calls loadViews() and loadRoutes().  We don't really
			// need routes for console apps, but we DO need the views registered
			// in case any console app or worker needs access to module views like
			// for sending emails.  So do it here only if console, else it will
			// load as usual in Foundation/Http/Middleware/LoadModules.php!
			Module::loadViews();
		}
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register Facades
		$facade = AliasLoader::getInstance();
		$facade->alias('Module', 'Mrcore\Foundation\Facades\Module');
		$facade->alias('Layout', 'Mrcore\Foundation\Facades\Layout');

		// Register UrlServiceProvider (laravel override) for mreschke https ssl termination fix
		$this->app->register('Mrcore\Foundation\Providers\UrlServiceProvider');

		// Mrcore Module Tracking
		Module::trace(get_class(), __function__);

		// Merge config
		$this->mergeConfigFrom(__DIR__.'/../Config/foundation.php', 'mrcore.foundation');

		// Configure Layout (this must be here, not in boot, not in middleware)
		Module::configureThemes();

		// Register all enabled mrcore modules
		$modules = Module::register();

		// Register our Artisan Commands
		$this->commands('Mrcore\Foundation\Console\Commands\ClearQueueCommand');
		$this->commands('Mrcore\Foundation\Console\Commands\InstallCommand');

	}

	/**
	 * Define publishing rules
	 *
	 * @return void
	 */
	private function definePublishing()
	{
		# App base path
		$path = realpath(__DIR__.'/../');

		// Foundation Config publishing rules
		// ./artisan vendor:publish --tag="mrcore.foundation.configs"
		$this->publishes([
			"$path/Config/foundation.php" => base_path('config/mrcore/foundation.php'),
		], 'mrcore.foundation.configs');

		// Modules Config publishing rules
		// ./artisan vendor:publish --tag="mrcore.modules.configs"
		$this->publishes([
			"$path/Config/modules.php" => base_path('config/modules.php'),
		], 'mrcore.modules.configs');

	}

}
