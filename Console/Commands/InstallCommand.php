<?php namespace Mrcore\Foundation\Console\Commands;

use Artisan;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class InstallCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mrcore:foundation:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and setup mrcore foundation.';

    protected $vendor;
    protected $package;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->info('Installing mrcore/foundation');

        $autoload = base_path('bootstrap/autoload.php');
        $autoloadContents = file_get_contents($autoload);
        if (str_contains($autoloadContents, "Mrcore Foundation")) {
            // Already installed asset manager
            $this->error("Foundation has already been installed!");
            exit();
        }

        // Publish Modules config
        $this->info("* Publishing Modules config");
        passthru('php artisan vendor:publish --tag mrcore.modules.configs');

        // Removing main routes.php
        $this->info("* Removing laravels routes.php");
        $routes = base_path('app/Http/routes.php');
        if (file_exists($routes)) {
            exec("rm -rf $routes");
            file_put_contents($routes, "<?php // Emptied by mrcore/foundation installer");
        }

        // Removing views
        $this->info("* Removing laravels views");
        $views = base_path('resources/views');
        if (file_exists($views)) {
            exec("rm -rf $views");
        }

        // Removing migrations
        $this->info("* Removing laravels migrations");
        $migrations = base_path('database/migrations');
        if (file_exists($migrations)) {
            exec("rm -rf $migrations");
        }

        // Removing User model
        $this->info("* Removing user model");
        $model = base_path('app/User.php');
        if (file_exists($model)) {
            exec("rm -rf $model");
        }

        // Whoops Errors
        // Never did this, but if you install whoops, then use the
        // Handler.php stub in this Commands/InstallStubs/Exceptions/Handler.php
        // you can get whopps back perfectly.

        // Install Bootstrap
        $bootstrapSearch = "define('LARAVEL_START', microtime(true));";
        $bootstrapReplace = "$bootstrapSearch

/*
|--------------------------------------------------------------------------
| Mrcore Foundation
|--------------------------------------------------------------------------
|
| Fire up the mrcore foundation to allow asset handling
| and other foundation support bootstraping.
|
*/

\$basePath = realpath(__DIR__.'/../');
\$runningInConsole = php_sapi_name() == 'cli';
if (file_exists(\"\$basePath/vendor/mrcore/foundation/Bootstrap/Start.php\")) {
    require \"\$basePath/vendor/mrcore/foundation/Bootstrap/Start.php\";
} else {
    require \"\$basePath/../Modules/Foundation/Bootstrap/Start.php\";
}";

        $this->info("* Installing Bootstrap to ./bootstrap/autoload.php");
        $autoloadContents = str_replace($bootstrapSearch, $bootstrapReplace, $autoloadContents);
        file_put_contents($autoload, $autoloadContents);

        // Done
        $this->info('Installation complete!');
    }
}
