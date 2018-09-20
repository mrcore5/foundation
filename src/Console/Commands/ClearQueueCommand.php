<?php namespace Mrcore\Foundation\Console\Commands;

use Illuminate\Console\Command;

class ClearQueueCommand extends Command
{
    protected $name = 'Clear Laravel Queue';
    protected $package = 'Mrcore\Foundation';
    protected $version = '1.0.0';
    protected $description = 'Delete the entire default queue';
    protected $signature = 'mrcore:foundation:queue:clear';

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
        $connection = config('queue.default');
        $this->info("Deleting all jobs from $connection queue");
        $queue = config("queue.connections.$connection.queue");
        $manager = app('Illuminate\Contracts\Queue\Factory');
        $count = 0;
        $connection = $manager->connection($connection);
        while ($job = $connection->pop($queue)) {
            $job->delete();
            $count++;
        }
        $this->info("Deleted $count jobs");
    }
}
