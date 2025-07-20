<?php

use Livewire\Volt\Volt;

it('can render', function () {
    $component = Volt::test('settings.openstack-cloud');

    $component->assertSee(__('Gérez vos connections OpenStack'));
});
