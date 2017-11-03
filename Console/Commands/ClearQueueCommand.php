<?php namespace Mrcore\Foundation\Console\Commands;

use Artisan;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ClearQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mrcore:foundation:queue:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the entire default queue.';

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
