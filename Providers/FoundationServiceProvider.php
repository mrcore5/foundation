<?php namespace Mrcore\Foundation\Providers;

use Config;
use Mrcore\Foundation\Support\ServiceProvider;

class FoundationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{

		// Add Facad Alias
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('Dynatron', 'Dynatron\Facades\Dynatron');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Layout Bindings
		# NO, I just use full 'Mrcore\Foundation\Support\Layout'
		#$this->app->bind('layout', 'Mrcore\Foundation\Support\Layout');

		// Facades
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('Layout', 'Mrcore\Foundation\Facades\Layout');

		// Register Themes Here instead of config/app.php because of Support/AssetProvider
		$themes = Config::get('theme.themes');
		if (isset($themes)) {
			foreach ($themes as $theme) {
				$this->app->register("$theme[namespace]\Providers\AppServiceProvider");
			}
		}
	}

}
