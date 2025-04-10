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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->string('name');
$table->enum('status', array (
  0 => 'open',
  1 => 'closed',
));

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
