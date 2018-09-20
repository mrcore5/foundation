<?php namespace Mrcore\Foundation\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $name = 'Mrcore Installer';
    protected $package = 'Mrcore/Foundation';
    protected $version = '5.7';
    protected $description = 'Install mrcore foundation into a fresh laravel install.';
    protected $signature = 'mrcore:foundation:install';

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
    public function handle()
    {
        $this->info("Mrcore Installation");
        $askInstall = $this->ask("You are about to install Mrcore $this->version.".PHP_EOL." This should only be installed on a FRESH version of laravel matching the same version $this->version.".PHP_EOL." Are you sure you want to install mrcore (y/n)?");
        if (strtolower($askInstall) != 'y') exit('done');

        // Installing .editorconfig
        $this->info("* Installing .editorconfig");
        copy(__DIR__.'/Stubs/.editorconfig', base_path('.editorconfig'));

        // Installing .gitignore
        $this->info("* Installing .gitignore");
        copy(__DIR__.'/Stubs/.gitignore', base_path('.gitignore'));

        // Publish Modules config
        $this->info("* Publishing Modules Config");
        passthru('php artisan vendor:publish --tag mrcore.modules.configs');

        // Making atrisan executable
        $this->info("* Making ./artisan Executable");
        exec("chmod a+x ".base_path('artisan'));

        // Removing database/migrations/*
        $this->info("* Removing database/migrations/*");
        $migrations = base_path('database/migrations');
        if (file_exists($migrations)) {
            exec("rm -rf $migrations/*");
        }

        // Removing User model
        $this->info("* Removing app/User.php model");
        $model = base_path('app/User.php');
        if (file_exists($model)) {
            exec("rm -rf $model");
        }

        // Replace 'timezone' => 'UTC' in config/app.php with 'timezone' => env('APP_TIMEZONE', 'UTC')
        $this->info("* Replace 'timezone' => 'UTC' in config/app.php With 'timezone' => env('APP_TIMEZONE', 'UTC')");
        $this->sed("'timezone' => 'UTC'", "'timezone' => env('APP_TIMEZONE', 'UTC')", base_path('config/app.php'));

        // Replace 'cipher' => 'AES-256-CBC' in config/app.php with cipher => env('APP_CIPHER', 'AES-256-CBC')
        $this->info("* Replace 'cipher' => 'AES-256-CBC' in config/app.php With cipher => env('APP_CIPHER', 'AES-256-CBC')");
        $this->sed("'cipher' => 'AES-256-CBC'", "'cipher' => env('APP_CIPHER', 'AES-256-CBC')", base_path('config/app.php'));

        // Replace 'queue' => 'default' in config/queue.php with 'queue' => env('QUEUE', 'default')
        $this->info("* Replace 'queue' => 'default' in config/queue.php With 'queue' => env('QUEUE', 'default')");
        $this->sed("'queue' => 'default'", "'queue' => env('QUEUE', 'default')", base_path('config/queue.php'));

        // Install Foundation Bootstrap to ./artisan
        $file = base_path('artisan');
        $content = file_get_contents($file);
        if (!str_contains($content, 'mrcore foundation')) {
            $search = "define('LARAVEL_START', microtime(true));";
            $replace = "$search

/*
|--------------------------------------------------------------------------
| Mrcore Foundation
|--------------------------------------------------------------------------
|
| Fire up the mrcore foundation to allow asset handling
| and other foundation support bootstraping.
|
*/

\$basePath = realpath(__DIR__);
\$runningInConsole = true;
require \"\$basePath/vendor/mrcore/foundation/src/Bootstrap/Start.php\";";

            $this->info("* Installing Foundation Bootstrap to $file");
            $content = str_replace($search, $replace, $content);
            file_put_contents($file, $content);
        }

        // Install Foundation Bootstrap to ./public/index.php
        $file = base_path('public/index.php');
        $content = file_get_contents($file);
        if (!str_contains($content, 'mrcore foundation')) {
            $search = "define('LARAVEL_START', microtime(true));";
            $replace = "$search

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
\$runningInConsole = false;
require \"\$basePath/vendor/mrcore/foundation/src/Bootstrap/Start.php\";";

            $this->info("* Installing Foundation Bootstrap to $file");
            $content = str_replace($search, $replace, $content);
            file_put_contents($file, $content);
        }

        // Done
        $this->info(PHP_EOL.'Installation complete!  Please visit this laravel install in your browser!');
    }

    /**
     * Run linux sed command to search and replace inside a file
     */
    protected function sed($search, $replace, $file)
    {
        exec("sed -i \"s/$search/$replace/g\" $file");
    }
}
