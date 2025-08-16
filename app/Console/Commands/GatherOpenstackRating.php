<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\OpenstackService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

final class GatherOpenstackRating extends Command
{
    /** @var string */
    protected $signature = 'app:gather-openstack-ratings
                            {start : The start date for gathering data}
                            {end : The end date for gathering data}
                            {--u|user= : The user to gather data for}
                            {--all : Gather data for all users}';

    /** @var string */
    protected $description = 'Gather OpenStack rating data';

    public function handle(): int
    {
        $openstackService = app(OpenstackService::class);

        $start = Carbon::parse($this->argument('start'));
        $end = Carbon::parse($this->argument('end'));
        $user = $this->option('user');
        $allUsers = $this->option('all');

        if ($allUsers && $user) {
            $this->error('You cannot specify both --all and --user options.');

            return Command::FAILURE;
        }

        if ($allUsers) {
            $this->info('Gathering data for all users from '.$start->toDateString().' to '.$end->toDateString());
            $users = User::all();
        } elseif ($user) {
            $this->info('Gathering data for user '.$user.' from '.$start->toDateString().' to '.$end->toDateString());
            $users = User::query()
                ->where('id', '=', $user)
                ->orWhere('email', '=', $user)
                ->orWhere('username', '=', $user)
                ->get();
        } else {
            $this->error('You must specify either --all or --user option.');

            return Command::FAILURE;
        }

        foreach ($users as $user) {
            $this->info("Gathering data for user: {$user->name} (ID: {$user->id})");
            $ratings = $openstackService->getRatingsFor(new CarbonPeriod($start, $end), $user);
        }

        return Command::SUCCESS;
    }
}
