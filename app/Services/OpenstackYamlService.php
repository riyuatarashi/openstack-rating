<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OpenstackCloud;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

final class OpenstackYamlService
{
    public function createCloudEntry(string $cloudYamlContent, ?User $user = null): void
    {
        $clouds = $this->parseCloudYaml($cloudYamlContent);
        unset($cloudYamlContent);

        $this->validateCloudData($clouds);

        foreach ($clouds as $cloud) {
            $openstackCloud = new OpenstackCloud;
            $openstackCloud->fill($cloud);
            $openstackCloud->user_id = $user->id ?? auth()->user()->id;
            $openstackCloud->save();
        }

        unset($clouds);
    }

    private function validateCloudData(Collection $clouds): void
    {
        /** @var array $defaultCloud */
        $defaultCloud = $this->parseCloudYaml(Storage::disk('local')->get('default-cloud.yaml'))->first();

        $diffs = [];

        foreach ($clouds as $cloud) {
            $diffs = array_merge($diffs, array_diff($defaultCloud, $cloud));
        }

        if (count($diffs) <= 5) {
            throw new InvalidArgumentException('The cloud data is too close to the default cloud configuration. Please ensure you have replaced all default values.');
        }
    }

    private function parseCloudYaml(string $cloudYamlContent): Collection
    {
        $yaml = Yaml::parse($cloudYamlContent);

        if (! isset($yaml['clouds'])) {
            throw new InvalidArgumentException('Invalid cloud YAML content: "clouds" key is missing.');
        }

        return collect($yaml['clouds'])
            ->map(function ($cloud, $name) {
                $cloud['name'] = $name;

                $auth = $cloud['auth'] ?? [];
                unset($cloud['auth']);

                foreach ($auth as $key => $value) {
                    if ($key === 'auth_url') {
                        $cloud[$key] = $value;

                        continue;
                    }

                    $cloud['auth_'.$key] = $value;
                }

                unset($auth);

                return $cloud;
            });
    }
}
