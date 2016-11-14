<?php namespace Mrcore\Foundation\Console\Commands;

use App;
use File;
use Config;
use Exception;
use InvalidArgumentException;
use Illuminate\Console\Command;

/**
 * Mrcore app/module helper command
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class AppCommand extends Command
{
    protected $signature;
    protected $description = "Mrcore app/module helper command";
    protected $app;
    protected $ns;
    protected $path;
    protected $paths;
    protected $relativePath;
    protected $connection;
    protected $seeder;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->signature = $this->signature.'
            {action? : db:migrate, db:rollback, make:migration etc...},
            {--usage : Show usage and examples},
            {--force : Force the operation to run when in production},
            {parameters?* : Any number of parameters for the specified action},
        ';
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Ex: db:migrate, make:migration
        $command = $this->argument('action');

        if ($this->option('usage') || !$command) {
            return $this->usage();
        }

        if (!str_contains($command, ':')) {
            throw new InvalidArgumentException();
        }

        list($section, $action) = explode(':', $command);

        $handleMethod = 'handle'.studly_case($section).'Commands';
        if (method_exists($this, $handleMethod)) {

            // Determine valid path to app
            foreach ($this->path as $path) {
                if (file_exists(base_path($path))) {

                    // Found valid path
                    $this->relativePath = $path;
                    $this->path = realpath(base_path($path));

                    // Get app specific paths
                    $this->configurePaths();

                    // Execute method
                    $this->$handleMethod($section, $action);
                    break;
                }
            }
        } else {
            throw new InvalidArgumentException("Argument $command not found");
        }
    }

    /**
     * Define each individual path for this app
     * @return void
     */
    protected function configurePaths()
    {
        // Define default paths, and allow overrides from passed $this->paths
        // This default is the original mrcore-root default, so no need to define for those
        if (!isset($this->paths)) $this->paths = [];
        $this->paths = array_merge([
            'psr4' => '',
            'assets' => 'Assets',
            'public' => 'Assets',
            'config' => 'Config',
            'database' => 'Database',
            'migrations' => 'Database/Migrations',
            'factories' => 'Database/Factories',
            'seeds' => 'Database/Seeds',
            'tests' => 'Tests',
            'views' => 'Views',
        ], $this->paths);

        // So not add full $this->path (base path) becuase
        // some items like migrations require relative path
    }

    /**
     * Handle db commands
     * @param  string $section
     * @param  string $action
     */
    protected function handleDbCommands($section, $action)
    {
        $method = $section.studly_case($action);
        if (method_exists($this, $method)) {
            if (!Config::has('database.connections.'.$this->connection)) {
                throw new Exception("Connection $this->connection does not exists");
            }
            $this->connection = array_merge(
                ['name' => $this->connection],
                Config::get('database.connections.'.$this->connection)
            );

            $this->$method();
        } else {
            throw new InvalidArgumentException("Argument $section:$action not found");
        }
    }

    /**
     * Handle make commands
     * @param  string $section
     * @param  string $action
     */
    protected function handleMakeCommands($section, $action)
    {
        // Name must be unique accross ALL apps since migrations have no namespace
        $method = $section.studly_case($action);
        if (method_exists($this, $method)) {

            $params = $this->argument('parameters');
            if (count($params) == 0) {
                throw new InvalidArgumentException("Please enter the proper parameters for this make command");
            }
            $this->$method($params);
        } else {
            throw new InvalidArgumentException("Argument $section:$action not found");
        }
    }

    /**
     * Handle test commands
     * @param  string $section
     * @param  string $action
     */
    protected function handleTestCommands($section, $action)
    {
        // Name must be unique accross ALL apps since migrations have no namespace
        $method = $section.studly_case($action);
        if (str_contains($method, "testPlay")) {
            // Can be test:play or test:play-custom
            $this->testPlay($action);
        } else {
            if (method_exists($this, $method)) {
                $this->$method();
            } else {
                throw new InvalidArgumentException("Argument $section:$action not found");
            }
        }
    }

    /**
     * Run the database migrations
     */
    protected function dbMigrate()
    {
        $this->call('migrate', [
            '--database' => $this->connection['name'],
            '--path' => $this->relativePath.'/'.$this->paths['migrations'],
            '--force' => $force = $this->input->getOption('force')
        ]);
    }

    /**
     * Rollback the last database migration
     */
    protected function dbRollback()
    {
        $this->call('migrate:rollback', [
            '--database' => $this->connection['name'],
            '--force' => $force = $this->input->getOption('force')
        ]);
    }

    /**
     * Rolls all of the currently applied migrations back
     */
    protected function dbReset()
    {
        $this->call('migrate:reset', [
            '--database' => $this->connection['name'],
            '--force' => $force = $this->input->getOption('force')
        ]);
    }

    /**
     * Reset and re-run all migrations (no seed)
     */
    protected function dbRefresh()
    {
        $this->dbReset();
        $this->dbMigrate();
    }

    /**
     * Seed the database with records
     */
    protected function dbSeed()
    {
        $this->call('db:seed', [
            '--database' => $this->connection['name'],
            '--class' => $this->seeder,
            '--force' => $force = $this->input->getOption('force')
        ]);
    }

    /**
     * Refresh and seed (reset, migrate, seed)
     */
    protected function dbReseed()
    {
        $this->dbRefresh();
        $this->dbSeed();
    }

    /**
     * Status on migrations
     */
    protected function dbStatus()
    {
        $this->call('migrate:status', [
            '--database' => $this->connection['name'],
            '--path' => $this->relativePath.'/'.$this->paths['migrations'],
        ]);
    }

    /**
     * Make console command
     */
    protected function makeCommand($params)
    {
        // Make file
        $name = $params[0];
        $path = $this->path.'/'.$this->paths['psr4']."/Console/Commands";
        $laravelFile = app_path('Console/Commands/MrcoreStubFile.php');
        $file = $this->make('command', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $signature = strtolower(str_replace("\\", ":", $this->ns)).":".str_replace("command", "", strtolower($name));
        $this->sed("App\\\\Console\\\\Commands", "$ns\\\\Console\\\\Commands", $file);
        $this->sed("MrcoreStubFile", $name, $file);
        $this->sed("command:name", $signature, $file);

        // Done
        $this->info("Remember to add it to your registerComamnds() method inside your service provier.");
    }

    /**
     * Make controller
     */
    protected function makeController($params)
    {
        // Make file
        $name = $params[0];
        $resource = isset($params[1]) ? $params[1] : null;
        $makeOptions = [];
        if ($resource == 'resource') {
            $makeOptions = ['--resource' => true];
        }

        $path = $this->path.'/'.$this->paths['psr4']."/Http/Controllers";
        $laravelFile = app_path('Http/Controllers/MrcoreStubFile.php');
        $file = $this->make('controller', $name, $path, $laravelFile, $makeOptions);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Http\\\\Controllers", "$ns\\\\Http\\\\Controllers", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make controller
     */
    protected function makeEvent($params)
    {
        // Make file
        $name = $params[0];
        $path = $this->path.'/'.$this->paths['psr4']."/Events";
        $laravelFile = app_path('Events/MrcoreStubFile.php');
        $file = $this->make('event', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Events", "$ns\\\\Events", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make job
     */
    protected function makeJob($params)
    {
        // Make file
        $name = $params[0];
        $sync = isset($params[1]) ? $params[1] : null;
        $makeOptions = [];
        if ($sync == 'sync') {
            $makeOptions = ['--sync' => true];
        }
        $path = $this->path.'/'.$this->paths['psr4']."/Jobs";
        $laravelFile = app_path('Jobs/MrcoreStubFile.php');
        $file = $this->make('job', $name, $path, $laravelFile, $makeOptions);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Events", "$ns\\\\Events", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make listener
     */
    protected function makeListener($params)
    {
        // Make file
        $name = $params[0];
        $queued = isset($params[1]) ? $params[1] : null;
        $makeOptions = ['--event' => 'SomeEvent'];
        if ($queued == 'queued') {
            $makeOptions['--queued'] = true;
        }
        $path = $this->path.'/'.$this->paths['psr4']."/Listeners";
        $laravelFile = app_path('Listeners/MrcoreStubFile.php');
        $file = $this->make('listener', $name, $path, $laravelFile, $makeOptions);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Listeners", "$ns\\\\Listeners", $file);
        $this->sed("App\\\\Events", "$ns\\\\Events", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make mail
     */
    protected function makeMail($params)
    {
        // Make file
        $name = $params[0];
        $path = $this->path.'/'.$this->paths['psr4']."/Mail";
        $laravelFile = app_path('Mail/MrcoreStubFile.php');
        $file = $this->make('mail', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Mail", "$ns\\\\Mail", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make mail
     */
    protected function makeMiddleware($params)
    {
        // Make file
        $name = $params[0];
        $path = $this->path.'/'.$this->paths['psr4']."/Http/Middleware";
        $laravelFile = app_path('Http/Middleware/MrcoreStubFile.php');
        $file = $this->make('middleware', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Http\\\\Middleware", "$ns\\\\Http\\\\Middleware", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make migration
     */
    protected function makeMigration($params)
    {
        // Make file
        $action = $params[0];
        $table = $params[1];

        #dd($this->relativePath.'/'.$this->paths['migrations']); //vendor/dynatron/roams/database/migrations
        #dd($this->relativePath); //vendor/dynatron/roams
        if ($action == 'create') {
            // Create new table
            $this->call('make:migration', [
                'name' => "create_${table}_".$this->name,
                '--path' => $this->relativePath.'/'.$this->paths['migrations'],
                '--create' => $table
            ]);
        } elseif ($action == 'update') {
            // Update table add column
            $column = $params[2];
            $this->call('make:migration', [
                'name' => "update_${table}_add_${column}_".$this->name,
                '--path' => $this->relativePath.'/'.$this->paths['migrations'],
                '--table' => $table
            ]);
        } else {
            throw new InvalidArgumentException("Valid arguments are create and update");
        }
    }

    /**
     * Make model
     */
    protected function makeModel($params)
    {
        // Make file
        $name = $params[0];
        $path = $this->path.'/'.$this->paths['psr4']."/Models";
        $laravelFile = app_path('MrcoreStubFile.php');
        $file = $this->make('model', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App", "$ns\\\\Models", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make notification
     */
    protected function makeNotification($params)
    {
        // Make file
        $name = $params[0];
        $path = $this->path.'/'.$this->paths['psr4']."/Notifications";
        $laravelFile = app_path('Notifications/MrcoreStubFile.php');
        $file = $this->make('notification', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Notifications", "$ns\\\\Notifications", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make policy
     */
    protected function makePolicy($params)
    {
        // Make file
        $name = $params[0];
        $model = isset($params[1]) ? $params[1] : null;
        $makeOptions = [];
        if (isset($model)) {
            $makeOptions = ['--model' => $model];
        }
        $path = $this->path.'/'.$this->paths['psr4']."/Policies";
        $laravelFile = app_path('Policies/MrcoreStubFile.php');
        $file = $this->make('policy', $name, $path, $laravelFile, $makeOptions);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Policies", "$ns\\\\Policies", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make request
     */
    protected function makeRequest($params)
    {
        // Make file
        $name = $params[0];
        $path = $this->path.'/'.$this->paths['psr4']."/Http/Requests";
        $laravelFile = app_path('Http/Requests/MrcoreStubFile.php');
        $file = $this->make('request', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("App\\\\Http\\\\Requests", "$ns\\\\Http\\\\Requests", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make seeder
     */
    protected function makeSeeder($params)
    {
        // Make file
        $name = studly_case($this->name).$params[0];
        $path = $this->path.'/'.$this->paths['seeds'];
        $laravelFile = base_path('database/seeds/MrcoreStubFile.php');
        $file = $this->make('seeder', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make test
     */
    protected function makeTest($params)
    {
        // Make file
        $name = $params[0];
        $path = $this->path.'/'.$this->paths['tests'];
        $laravelFile = base_path('tests/MrcoreStubFile.php');
        $file = $this->make('test', $name, $path, $laravelFile, []);

        // Search and replace content
        $ns = str_replace("\\", "\\\\\\", $this->ns);
        $this->sed("<?php", "<?php namespace $ns\\\\Test;\\n\\nuse TestCase;", $file);
        $this->sed("MrcoreStubFile", $name, $file);
    }

    /**
     * Make the make file
     */
    protected function make($type, $name, $path, $laravelFile, $params)
    {
        $file = "$path/$name.php";

        if (file_exists($file)) {
            throw new Exception("File $file already exists");
        }

        // Create actual laravel file
        $params = array_merge([
            'name' => 'MrcoreStubFile',
        ], $params);
        $this->call("make:$type", $params);

        // Move command to mrcore app folder
        if (!file_exists($path)) {
            exec("mkdir -p $path");
        }
        File::move($laravelFile, $file);
        return $file;
    }

    /**
     * Run phpunit tests
     */
    protected function testRun()
    {
        $params = $this->argument('parameters');
        $filter = '';
        if (count($params) == 1) {
            // Adding a filter`
            $filter = "--filter=$params[0]";
        }
        passthru("cd ".base_path()." && phpunit $filter ".$this->path."/".$this->paths['tests']."/");
    }

    /**
     * Run phpunit test play sandbox
     * @param  string $action = 'play'
     */
    protected function testPlay($action = 'play')
    {
        $params = implode(' ', $this->argument('parameters'));
        passthru("cd ".base_path()." && phpunit ".$this->path."/".$this->paths['tests']."/ $action $params");
    }

    /**
     * Display usage and examples
     */
    protected function usage()
    {
        $this->line("Mrcore5 Application Command Line");
        $this->comment('db');
        $this->info('  db:migrate                     Run the database migrations');
        $this->info('  db:rollback                    Rollback the last database migration');
        $this->info('  db:reset                       Rollback all database migrations');
        $this->info('  db:refresh                     Reset and re-run all migrations');
        $this->info('  db:seed                        Seed the database with records');
        $this->info('  db:reseed                      Refresh and seed (reset, migrate, seed)');
        $this->info('  db:status                      Show the status of each migration');

        // I did not implement make:provider
        $this->comment('make');
        $this->info('  make:command                   Create a new Artisan command');
        $this->info('  make:controller                Create a new controller class');
        $this->line('    Example: make:controller UserController');
        $this->line('    Example: make:controller UserController resource');
        $this->info('  make:event                     Create a new event class');
        $this->info('  make:job                       Create a new job class');
        $this->line('    Example: make:job SomeJob');
        $this->line('    Example: make:job SomeSyncJob sync');
        $this->info('  make:listener                  Create a new event listener class');
        $this->line('    Example: make:listener SomeListener');
        $this->line('    Example: make:listener SomeListener queued');
        $this->info('  make:mail                      Create a new email class');
        $this->info('  make:middleware                Create a new middleware class');
        $this->info('  make:migration                 Create a new migration file');
        $this->line('    Example: make:migration create users');
        $this->line('    Example: make:migration update users new_col');
        $this->info('  make:model                     Create a new Eloquent model class');
        $this->info('  make:notification              Create a new notification class');
        $this->info('  make:policy                    Create a new policy class');
        $this->line('    Example: make:policy MyPolicy');
        $this->line('    Example: make:policy MyModelPolicy SomeModel');
        $this->info('  make:request                   Create a new form request class');
        $this->info('  make:seeder                    Create a new seeder class');
        $this->info('  make:test                      Create a new test class');

        $this->comment('test');
        $this->info('  test:run                       Run tests');
        $this->info('  test:run ThisFile              Run tests only for specific test file filter');
        $this->info('  test:play                      Run play sandbox');
        $this->info('  test:play-custom               Run play sandbox only custom method');
    }

    /**
     * Sed helper
     * @param  string $search
     * @param  string $replace
     * @param  string $file
     * @return void
     */
    protected function sed($search, $replace, $file)
    {
        exec("sed -i 's`$search`$replace`g' $file");
    }
}
