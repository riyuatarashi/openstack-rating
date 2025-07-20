<?php

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Volt;

it('can handle yaml', function () {
    Storage::fake('local');

    $yaml = TemporaryUploadedFile::fake()->createWithContent(
        'test.yaml', 'test.yaml content'
    );

    $component = Volt::test('settings.openstack-cloud-form');
    $component->set('cloud_yaml', $yaml);

    $component->call('rendering');

    ray($component->content());

    $component->assertHasNoErrors();
    $component->assertSee('test.yaml');
    $component->assertSet('cloud_yaml', null); // the file is processed and set to null
    $component->assertSet('cloud_yaml_content', 'test.yaml content');
});

it('fails on other file types', function () {
    Storage::fake('local');

    $file = TemporaryUploadedFile::fake()->createWithContent(
        'test.txt', 'test.txt content'
    );

    $component = Volt::test('settings.openstack-cloud-form');
    $component->set('cloud_yaml', $file);

    $component->call('rendering');

    $component->assertHasErrors(['cloud_yaml']);
});
