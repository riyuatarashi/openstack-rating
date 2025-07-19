<?php

use Livewire\Volt\Volt;

it('can render', function () {
    $component = Volt::test('create-openstack-cloud');

    $component->assertSee('');
});
