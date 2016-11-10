<?php namespace Mrcore\Foundation\Console\Commands;

use File;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AppMakeCommand extends Command
{
    protected $name = 'App Make';
    protected $package = 'Mrcore/Foundation';
    protected $version = '1.0.0';
    protected $description = 'Make a new mrcore application';
    protected $signature = 'mrcore:foundation:app:make
        {name : Application name in lowercase your-vendor/your-package format},
        {--template= : Appstub template to use. See branches on https://github.com/mrcore5/appstub},
        {--path= : Optional alternate apps path}
    ';

    #array('template', InputOption::REQUIRED, 'Appstub template to install'),
    #array('app', InputArgument::REQUIRED, 'App name in laravel vendor/package format'),

    #protected $appVendor;
    #protected $appPackage;

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
        // Input
        $name = $this->argument('name');
        $template = $this->option('template');
        $appBasePath = $this->option('path') ?: realpath(base_path("../Apps"));
        if (!isset($template)) {
            exit($this->error('You must provide a valid --template (which is a git branch on mrcore5/appstub'));
        }

        // Get vendor and package name
        list($vendor, $package) = explode('/', $name);

        // Get app path
        $path = "$appBasePath/".studly_case($vendor)."/".studly_case($package);
        if (is_dir($path)) {
            exit($this->error('App already exists'));
        }
        exec("mkdir -p $path");

        // Git clone (and remove .git)
        $cmdStatus = exec("cd $path && git clone -b $template --depth 1 https://github.com/mreschke/mrcore-appstub . && rm -rf .git && echo 0");
        if ($cmdStatus != "0") {
            exit($this->error("Failed.  Perhaps the branch (app template) was not found.  Check https://github.com/mrcore5/appstub branches to see valid templates"));
        }

        // Replace file contents
        $files = File::allFiles($path);
        foreach ($files as $file) {
            $this->replace($file, $vendor, $package);
        }

        // Rename files
        // FIXME: these are the type of custom issues that would be better handled inside
        // each appstub themselves, to create better standalone and self defined appstubs
        if (str_contains($template, 'standalone')) {
            // This is a standalone appstub with /src/ folder
            exec("mv $path/config/appstub.php $path/config/$package.php");
        } elseif (str_contains($template, 'src')) {
            // This is a mrcore5 module appstub with /src/ folder
            exec("mv $path/src/Providers/AppstubServiceProvider.php $path/src/Providers/".studly_case($package)."ServiceProvider.php");
            exec("mv $path/config/appstub.php $path/config/$package.php");
        } else {
            // This is a mrcore5 module appstub with everything in root folder
            exec("mv $path/Providers/AppstubServiceProvider.php $path/Providers/".studly_case($package)."ServiceProvider.php");
            exec("mv $path/Database/Seeds/AppstubSeeder.php $path/Database/Seeds/".studly_case($package)."Seeder.php");
            exec("mv $path/Database/Seeds/AppstubTestSeeder.php $path/Database/Seeds/".studly_case($package)."TestSeeder.php");
            exec("mv $path/Http/Controllers/AppstubController.php $path/Http/Controllers/".studly_case($package)."Controller.php");
            exec("mv $path/Facades/Appstub.php $path/Facades/".studly_case($package).".php");
            exec("mv $path/Config/appstub.php $path/Config/$package.php");
        }

        // Composer update (no, we use componet path repo now, so no app composer needed)
        #exec("cd $path && composer update");
        #exec("cd $path && composer dump-autoload -o");
    }

    /**
     * Replace appstub text with proper vendor and package insde this one $file
     */
    protected function replace($file, $vendor, $package)
    {
        $vendor = $vendor;
        $package = $package;
        $app = "$vendor/$package";
        $path = studly_case($vendor)."/".studly_case($package);
        $namespace = studly_case($vendor)."\\\\".studly_case($package);
        $doubleNamespace = studly_case($vendor)."\\\\\\\\".studly_case($package);
        $word = studly_case($vendor)." ".studly_case($package);

        // Order is critical
        $this->sed("mrcore/appstub", $app, $file);
        $this->sed("Mrcore Appstub", $word, $file);
        $this->sed("mreschke/mrcore-appstub", $app, $file);
        $this->sed('Mrcore/Appstub', $path, $file);
        $this->sed('Mrcore\\\\\\\\Appstub', $doubleNamespace, $file);
        $this->sed('Mrcore\\\\Appstub', $namespace, $file);
        $this->sed('mrcore:appstub', "$vendor:$package", $file);
        $this->sed('mrcore:$app', "$vendor:\$app", $file);
        $this->sed('mrcore\.appstub', "$vendor.$package", $file);
        $this->sed('appstub::', "$package::", $file);
        $this->sed('AppstubController', studly_case($package).'Controller', $file);
        $this->sed('AppstubServiceProvider', studly_case($package).'ServiceProvider', $file);
        $this->sed('exists appstub', "exists ".str_replace('-', '_', $package), $file);
        $this->sed('database appstub', "database ".str_replace('-', '_', $package), $file);
        $this->sed('database=appstub', "database=".str_replace('-', '_', $package), $file);
        $this->sed('AppstubTestSeeder', studly_case($package)."TestSeeder", $file);
        $this->sed('Appstub', studly_case($package), $file);
        $this->sed("appstub", $package, $file);
    }

    /**
     * Run linux sed command to search and replace inside a file
     */
    protected function sed($search, $replace, $file)
    {
        exec("sed -i 's`$search`$replace`g' $file");
    }
}
