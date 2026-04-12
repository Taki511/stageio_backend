<?php

namespace App\Console\Commands;

use App\Services\AutoActionService;
use Illuminate\Console\Command;

class RunAutoActions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-actions {--show : Display detailed results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run auto-actions for applications (cancel pending, cancel unconfirmed, validate confirmed)';

    /**
     * Execute the console command.
     */
    public function handle(AutoActionService $service): int
    {
        $this->info('Running auto-actions...');
        $this->info('Timezone: ' . config('app.timezone'));
        $this->info('Current time: ' . now()->toDateTimeString());
        $this->newLine();

        $results = $service->runAll();

        // Display results
        $this->table(
            ['Action', 'Count'],
            [
                ['Pending applications cancelled (14 days)', $results['pending_cancelled']],
                ['Unconfirmed applications cancelled (14 days)', $results['unconfirmed_cancelled']],
                ['Confirmed applications validated (7 days)', $results['confirmed_validated']],
                ['Internships auto-completed (end date passed)', $results['internships_completed']],
            ]
        );

        $this->newLine();
        $this->info('Auto-actions completed at: ' . $results['timestamp']);

        return Command::SUCCESS;
    }
}
