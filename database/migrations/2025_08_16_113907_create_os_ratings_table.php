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
        if (Schema::hasTable('os_ratings')) {
            return;
        }

        Schema::create('os_ratings', function (Blueprint $table) {
            $table->id();

            $table->float('rating');
            $table->float('volume');

            $table->timestamp('begin');
            $table->timestamp('end');
            $table->string('service');

            $table->timestamps();
        });

        Schema::table('os_ratings', function (Blueprint $table) {
            $table->foreignId('os_resource_id')
                ->constrained('os_resources')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['begin', 'end', 'service', 'os_resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('os_ratings');
    }
};
