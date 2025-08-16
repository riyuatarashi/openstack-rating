<?php

namespace App\Console\Commands;

use App\Models\OsProject;
use App\Models\OsRating;
use App\Models\OsResource;
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
            $progressBar = $this->output->createProgressBar(
                (int) collect($dataframes)
                    ->reduce(fn ($carry, $item): float|int => $carry + count($item['resources']), 0)
            );

            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->setMessage('');
            $progressBar->start();

            foreach ($dataframes as $dataframe) {
                foreach ($dataframe['resources'] as $resource) {
                    $progressBar->setMessage('Processing resource: '.$resource['desc']['id']);
                    $OSProject = OsProject::query()->firstOrCreate(
                        [
                            'project_identifier' => $resource['desc']['project_id'],
                        ],
                        [
                            'project_identifier' => $resource['desc']['project_id'],
                            'name' => $resource['desc']['project_id'],
                            'os_cloud_id' => $cloud->id,
                        ]
                    );

                    $OSResource = OsResource::query()->firstOrCreate(
                        [
                            'resource_identifier' => $resource['desc']['id'],
                        ],
                        [
                            'resource_identifier' => $resource['desc']['id'],
                            'name' => $resource['desc']['flavor_name'] ?? $resource['service'],
                            'flavor_name' => $resource['desc']['flavor_name'] ?? null,
                            'state' => $resource['desc']['state'] ?? null,
                            'os_project_id' => $OSProject->id,
                        ]
                    );

                    if (
                        OsRating::query()
                            ->where('begin', '=', Carbon::parse($dataframe['begin']))
                            ->where('end', '=', Carbon::parse($dataframe['end']))
                            ->where('service', '=', $resource['service'])
                            ->where('os_resource_id', '=', $OSResource->id)
                            ->exists()
                    ) {
                        $progressBar->setMessage('Skipped');
                        $progressBar->advance();

                        continue; // Skip if the rating already exists
                    }

                    $OSRating = new OsRating;
                    $OSRating->fill([
                        'rating' => (float) $resource['rating'],
                        'volume' => (float) $resource['volume'],
                        'begin' => Carbon::parse($dataframe['begin']),
                        'end' => Carbon::parse($dataframe['end']),
                        'service' => $resource['service'],
                        'os_resource_id' => $OSResource->id,
                    ]);
                    $OSRating->save();

                    $progressBar->advance();
                }
            }

            $progressBar->finish();
        }

        $this->info("\nGathering OpenStack ratings completed successfully.");

        return Command::SUCCESS;
    }
}
