<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openstack_clouds', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->string('region_name');
            $table->string('interface')->default('public'); // public, internal, admin
            $table->string('identity_api_version')->default('3');

            $table->string('auth_url');
            $table->string('auth_username')->comment('Encrypted');
            $table->string('auth_password')->comment('Encrypted');
            $table->string('auth_project_id')->comment('Encrypted');
            $table->string('auth_project_name')->nullable();
            $table->string('auth_user_domain_name')->default('Default');

            $table->timestamps();
        });

        // Foreign key constraints can be added later if needed
        Schema::table('openstack_clouds', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
        });

        // Add indexes for faster lookups
        Schema::table('openstack_clouds', function (Blueprint $table) {
            $table->index(['name']);
            $table->index(['region_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openstack_clouds');
    }
};
