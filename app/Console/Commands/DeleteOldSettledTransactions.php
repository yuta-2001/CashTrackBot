<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteOldSettledTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:delete-old-settled-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old settled transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateThreshold = Carbon::now()->subDays(5);

        Transaction::settled()
            ->where('settled_at', '<', $dateThreshold)
            ->delete();

        $this->info('Deleted old settled transactions.');
    }
}
