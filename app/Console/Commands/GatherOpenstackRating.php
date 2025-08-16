<?php

namespace App\Console\Commands;

use App\Jobs\RegisterOsRatings;
use App\Models\User;
use App\Services\OpenstackService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

final class GatherOpenstackRating extends Command
{
    /** @var string */
    protected $signature = 'app:gather-os-ratings
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
                ->when(is_numeric($user), fn ($query) => $query->where('id', '=', (int) $user))
                ->when(! is_numeric($user), fn ($query) => $query
                    ->where('email', '=', $user)
                    ->orWhere('name', '=', $user)
                )
                ->get();
        } else {
            $this->error('You must specify either --all or --user option.');

            return Command::FAILURE;
        }

        foreach ($users as $user) {
            $this->info("Gathering data for user: {$user->name} (ID: {$user->id})");
            $clouds = $user->clouds;

            if ($clouds->isEmpty()) {
                $this->warn("User {$user->name} (ID: {$user->id}) has no OpenStack clouds configured.");

                continue;
            }

            /** @var \App\Models\OsCloud $cloud */
            $cloud = $clouds->first();

            $this->info('Gathering data ...');
            $dataframes = $openstackService->getRatingsFor(new CarbonPeriod($start, $end), $user);

            if (empty($dataframes)) {
                $this->warn('No dataframes found for user '.$user->name.' (ID: '.$user->id.').');

                continue;
            }

            $this->info('Processing dataframes ...');

            foreach (array_chunk($dataframes, RegisterOsRatings::DATAFRAMES_PER_JOB) as $dataframes_chunk) {
                RegisterOsRatings::dispatch($dataframes_chunk, $cloud->id);
            }
        }

        $this->info("\nGathering OpenStack ratings completed successfully.");

        return Command::SUCCESS;
    }
}
