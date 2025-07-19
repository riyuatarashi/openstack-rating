<?php

use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Symfony\Component\Yaml\Yaml;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Openstack Cloud')" :subheading=" __('Gérez vos connections OpenStack')">
        <livewire:settings.openstack-cloud-form />
    </x-settings.layout>
</section>
