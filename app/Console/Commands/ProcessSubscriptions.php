<?php

namespace App\Console\Commands;

use App\Actions\Subscriptions\ProcessRenewals;
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
    public function handle(ProcessRenewals $processRenewals)
    {
        $this->info('Processing subscription renewals...');
        $processRenewals->execute();
        $this->info('Done processing subscription renewals.');
    }
}
