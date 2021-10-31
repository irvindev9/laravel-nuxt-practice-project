<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\User;

class UpdateRankingsCommand extends Command
{
    protected $signature = 'update:rankings';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $ambassadors = User::ambassadors()->get();

        $bar = $this->output->createProgressBar($ambassadors->count());

        $bar->start();

        $ambassadors->each(function (User $user) use ($bar){
            Redis::zadd('rankings', (int)$user->revenue, $user->name);

            $bar->advance();
        });

        $bar->finish();

        return Command::SUCCESS;
    }
}
