<?php

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Symfony\Component\Yaml\Yaml;

new class extends Component {
    public Collection $openstackClouds;

    public function boot(): void
    {
        $this->openstackClouds = auth()->user()->openstackClouds;
    }

    public function rendering(bool $hasToRetrieveClouds = false): void
    {
        if ($hasToRetrieveClouds) {
            $this->openstackClouds = auth()->user()->openstackClouds->fresh();
        }
    }

    #[On('configCreated')]
    public function configCreated(): void
    {
        $this->rendering(true);
    }

    public function removeCloud(int $cloudId): void
    {
        $cloud = auth()->user()->openstackClouds()->find($cloudId);

        if ($cloud) {
            $cloud->delete();
            $this->rendering(true);
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Openstack Cloud')" :subheading=" __('Gérez vos connections OpenStack')">
        <div class="grid grid-cols-3 gap-4">
            @if($this->openstackClouds->isEmpty())
                <flux:text class="col-span-2">
                    {{ __('Aucun cloud OpenStack n\'est configuré pour le moment.') }}
                </flux:text>

                <div class="col-start-3 justify-self-end">
                    <livewire:settings.openstack-cloud-form />
                </div>
            @endif

            @foreach($this->openstackClouds as $cloud)
                <div class="col-span-3 flex items-center">
                    <flux:dropdown>
                        <flux:button icon="cog-8-tooth" variant="ghost" />

                        <flux:menu>
                            <!-- flux:menu.separator /-->
                            <flux:menu.item variant="danger" icon="trash" wire:click="removeCloud({{ $cloud->id }})">
                                @lang('Supprimer')
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <flux:icon.grip-vertical />

                    <flux:heading class="ml-2">{{ $cloud->name }}</flux:heading>
                    <flux:text class="ml-2">from {{ $cloud->auth_url }}</flux:text>
                </div>
            @endforeach
        </div>
    </x-settings.layout>
</section>
