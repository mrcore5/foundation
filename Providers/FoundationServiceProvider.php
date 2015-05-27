<?php namespace Mrcore\Modules\Foundation\Providers;

use App;
use View;
use Input;
use Config;
use Layout;
use Module;
use Illuminate\Foundation\AliasLoader;
use Mrcore\Modules\Foundation\Support\ServiceProvider;

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
	public function boot()
	{
		// Mrcore Module Tracking
		Module::trace(get_class(), __function__);

		// Define publishing rules
		$this->definePublishing();		

		// Load our custom macros
		require __DIR__.'/../Support/Macros.php';

		// Register Middleware (in boot() to be last, not register())
		$kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');
		$kernel->pushMiddleware('Mrcore\Modules\Foundation\Http\Middleware\LoadModules');

		// Configure layout modes
		$simpleMode = Input::get('simple');
		if (isset($simpleMode) || Input::get('viewmode') == 'simple') {
			Layout::mode('simple');
		}
		$rawMode = Input::get('raw');
		if (isset($rawMode) || Input::get('viewmode') == 'raw') {
			Layout::mode('raw');
		}
		$defaultMode = Input::get('default');
		if (isset($defaultMode) || Input::get('viewmode') == 'default') {
			Layout::mode('default');
		}

		if (App::runningInConsole()) {
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
		$facade->alias('Module', 'Mrcore\Modules\Foundation\Facades\Module');
		$facade->alias('Layout', 'Mrcore\Modules\Foundation\Facades\Layout');

		// Register UrlServiceProvider (laravel override) for mreschke https ssl termination fix
		$this->app->register('Mrcore\Modules\Foundation\Providers\UrlServiceProvider');

		// Mrcore Module Tracking
		Module::trace(get_class(), __function__);

		// Configure Layout (this must be here, not in boot, not in middleware)
		Module::configureThemes();		

		// Register all enabled mrcore modules
		$modules = Module::register();

		// Register our Artisan Commands
		$this->commands('Mrcore\Modules\Foundation\Console\Commands\InstallCommand');		

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

		// Merge config
		// Notice, do NOT merge modules.php, must publish this one and override the whole file
		$this->mergeConfigFrom("$path/Config/foundation.php", 'mrcore.foundation');

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
