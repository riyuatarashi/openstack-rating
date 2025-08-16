<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OsProject;
use App\Models\OsRating;
use App\Models\OsResource;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

final class RegisterOsRatings implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public const DATAFRAMES_PER_JOB = 100;

    public function __construct(
        protected readonly array $dataframes,
        protected readonly int $cloudId,
    ) {}

    public function handle(): void
    {
        foreach ($this->dataframes as $dataframe) {
            foreach ($dataframe['resources'] as $resource) {
                $OSProject = OsProject::query()->firstOrCreate(
                    [
                        'project_identifier' => $resource['desc']['project_id'],
                    ],
                    [
                        'project_identifier' => $resource['desc']['project_id'],
                        'name' => $resource['desc']['project_id'],
                        'os_cloud_id' => $this->cloudId,
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
            }
        }
    }
}
