<?php

namespace App\Console\Commands;

use App\Models\BlockedName;
use Illuminate\Console\Command;

class UnblockName extends Command
{
    protected $signature = 'spam:unblock-name {name}';
    protected $description = 'Remove a name from the block list';

    public function handle(): int
    {
        $name = $this->argument('name');
        BlockedName::where('name', $name)->delete();
        $this->info('Unblocked: '.$name);
        return self::SUCCESS;
    }
}

