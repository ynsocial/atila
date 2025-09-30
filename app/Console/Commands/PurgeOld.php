<?php

namespace App\Console\Commands;

use App\Models\ContactSubmission;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PurgeOld extends Command
{
    protected $signature = 'spam:purge-old {--days=90}';
    protected $description = 'Purge old contact submissions and throttle data';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $before = Carbon::now()->subDays($days);
        $deleted = ContactSubmission::where('created_at', '<', $before)->delete();
        $this->info('Deleted submissions: '.$deleted);
        return self::SUCCESS;
    }
}

