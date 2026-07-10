<?php

namespace App\Console\Commands;

use App\Jobs\RenewSubscriptionsJob;
use Illuminate\Console\Command;

class ProcessSubscriptions extends Command
{
    protected $signature = 'subscriptions:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due subscriptions and charge payment methods';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Dispatching subscription renewals job...');
        RenewSubscriptionsJob::dispatch();
        $this->info('Done dispatching subscription renewals.');
    }
}
