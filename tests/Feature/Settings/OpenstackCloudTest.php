<?php

use App\Models\User;
use Livewire\Volt\Volt;

it('can render', function () {
    auth()->setUser(User::factory()->create());
    $component = Volt::test('settings.openstack-cloud');

    $component->assertSee(__('Gérez vos connections OpenStack'));
});
