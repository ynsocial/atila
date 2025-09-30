<?php

namespace App\Console\Commands;

use App\Models\BlockedName;
use Illuminate\Console\Command;

class BlockName extends Command
{
    protected $signature = 'spam:block-name {name} {--reason=}';
    protected $description = 'Block a name from submitting the contact form';

    public function handle(): int
    {
        $name = $this->argument('name');
        $reason = $this->option('reason');
        BlockedName::updateOrCreate(['name' => $name], [
            'reason' => $reason,
            'added_by' => 'cli',
        ]);
        $this->info('Blocked: '.$name);
        return self::SUCCESS;
    }
}

