<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('os_resources')) {
            return;
        }

        Schema::create('os_resources', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('description')->nullable();
            $table->string('resource_identifier')->unique();
            $table->string('flavor_name')->nullable();
            $table->string('state')->nullable();

            $table->timestamps();
        });

        Schema::table('os_resources', function (Blueprint $table) {
            $table->foreignId('os_project_id')
                ->constrained('os_projects')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('os_resources');
    }
};
